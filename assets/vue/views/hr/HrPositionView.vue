<template>
  <div class="positions-page">
    <SectionHeader :title="t('Positions')">
      <BaseButton
        :label="t('Add position')"
        icon="plus"
        type="success"
        @click="openCreate"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="isLoading"
      :values="items"
    >
      <Column
        :header="t('Staff member')"
        field="userFullName"
        sortable
      />
      <Column
        :header="t('Username')"
        field="userUsername"
      />
      <Column
        :header="t('Position title')"
        field="functionInUnitTitle"
        sortable
      />
      <Column
        :header="t('Business unit')"
        field="businessUnitTitle"
        sortable
      />
      <Column :header="t('Start date')">
        <template #body="{ data }">{{ data.startDate }}</template>
      </Column>
      <Column :header="t('End date')">
        <template #body="{ data }">
          <span :class="endDateClass(data.endDate)">{{ data.endDate ?? "—" }}</span>
        </template>
      </Column>
      <Column :header="t('FTE')">
        <template #body="{ data }">{{ data.contractEtp ?? "—" }}</template>
      </Column>
      <Column :header="t('Boss')">
        <template #body="{ data }">
          <span
            v-if="data.isBoss"
            class="text-green-600 font-semibold"
            >✓</span
          >
        </template>
      </Column>
      <Column
        :header="t('Actions')"
        style="width: 100px"
      >
        <template #body="{ data }">
          <div class="flex gap-1">
            <BaseButton
              icon="pencil"
              only-icon
              size="small"
              type="secondary-text"
              @click="openEdit(data)"
            />
            <BaseButton
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="dialogVisible"
      :style="{ width: '560px' }"
      :title="editingItem ? t('Edit position') : t('Add position')"
    >
      <BaseSelect
        id="position-user"
        v-model="form.user"
        :hast-empty-value="true"
        :label="t('Staff member')"
        :options="userOptions"
        name="position_user"
      />
      <BaseSelect
        id="position-function-in-unit"
        v-model="form.functionInUnit"
        :hast-empty-value="true"
        :label="t('Function-unit association')"
        :options="functionInUnitOptions"
        name="position_function_in_unit"
      />
      <BaseCalendar
        id="position-date-range"
        v-model="dateRange"
        :label="t('Period')"
        type="range"
      />
      <BaseSelect
        id="position-branch"
        v-model="form.branch"
        :label="t('Branch')"
        :options="branchOptions"
        allow-cleared
        name="position_branch"
      />
      <BaseSelect
        id="position-geographic-zone"
        v-model="form.geographicZone"
        :label="t('Geographic zone')"
        :options="geographicZoneOptions"
        allow-cleared
        name="position_geographic_zone"
      />
      <div class="flex gap-4 items-end">
        <div class="flex-1">
          <BaseInputNumber
            id="position-contract-etp"
            v-model="form.contractEtp"
            :label="t('FTE (0–1)')"
            :max="1"
            :min="0"
            :step="0.01"
            name="position_contract_etp"
          />
        </div>
        <div class="pb-1">
          <BaseCheckbox
            id="position-is-boss"
            v-model="form.isBoss"
            :label="t('Is manager/boss')"
            name="position_is_boss"
          />
        </div>
      </div>

      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="dialogVisible = false"
        />
        <BaseButton
          :disabled="isSaving"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="save"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import axios from "axios"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()

const items = ref([])
const users = ref([])
const functionInUnits = ref([])
const branches = ref([])
const geographicZones = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const dialogVisible = ref(false)
const editingItem = ref(null)
const dateRange = ref(null)

const emptyForm = () => ({
  user: null,
  functionInUnit: null,
  branch: null,
  geographicZone: null,
  contractEtp: 1,
  isBoss: false,
})
const form = ref(emptyForm())

const userOptions = computed(() =>
  users.value.map((u) => ({ label: `${u.fullName} (${u.username})`, value: u["@id"] })),
)

const functionInUnitOptions = computed(() =>
  functionInUnits.value.map((fiu) => ({ label: `${fiu.title} — ${fiu.businessUnitTitle}`, value: fiu["@id"] })),
)

const branchOptions = computed(() => branches.value.map((b) => ({ label: b.title, value: b["@id"] })))

const geographicZoneOptions = computed(() => geographicZones.value.map((gz) => ({ label: gz.title, value: gz["@id"] })))

function endDateClass(date) {
  if (!date) return ""
  const days = (new Date(date) - new Date()) / 86400000
  if (days < 0) return "text-red-600 font-semibold"
  if (days < 30) return "text-orange-500 font-semibold"
  return ""
}

async function load() {
  isLoading.value = true
  try {
    const [posRes, userRes, fiuRes, branchRes, gzRes] = await Promise.all([
      axios.get("/hr/positions-data"),
      axios.get("/api/users?pagination=false&properties[]=id&properties[]=fullName&properties[]=username"),
      axios.get("/api/function_in_units?pagination=false"),
      axios.get("/hr/hr-branches-data"),
      axios.get("/api/geographic_zones?pagination=false"),
    ])
    items.value = posRes.data
    users.value = userRes.data["hydra:member"] ?? []
    functionInUnits.value = fiuRes.data["hydra:member"] ?? []
    branches.value = branchRes.data["hydra:member"] ?? []
    geographicZones.value = gzRes.data["hydra:member"] ?? []
  } finally {
    isLoading.value = false
  }
}

function openCreate() {
  editingItem.value = null
  form.value = emptyForm()
  dateRange.value = null
  dialogVisible.value = true
}

function openEdit(item) {
  editingItem.value = item
  form.value = {
    user: item.userIri ?? null,
    functionInUnit: item.functionInUnitIri ?? null,
    branch: null,
    geographicZone: null,
    contractEtp: item.contractEtp ?? 1,
    isBoss: item.isBoss,
  }
  const startDate = item.startDate ? new Date(item.startDate) : null
  const endDate = item.endDate ? new Date(item.endDate) : null
  dateRange.value = startDate ? [startDate, endDate] : null
  dialogVisible.value = true
}

async function save() {
  if (!form.value.user || !form.value.functionInUnit || !dateRange.value?.[0]) return
  isSaving.value = true
  try {
    const payload = {
      user: form.value.user,
      functionInUnit: form.value.functionInUnit,
      startDate: dateRange.value?.[0] ? new Date(dateRange.value[0]).toISOString() : null,
      endDate: dateRange.value?.[1] ? new Date(dateRange.value[1]).toISOString() : null,
      branch: form.value.branch || null,
      geographicZone: form.value.geographicZone || null,
      contractEtp: form.value.contractEtp != null ? String(form.value.contractEtp) : null,
      isBoss: form.value.isBoss,
    }
    if (editingItem.value) {
      await axios.put(`/api/user_to_function_in_units/${editingItem.value.id}`, payload)
    } else {
      await axios.post("/api/user_to_function_in_units", payload)
    }
    dialogVisible.value = false
    await load()
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      await axios.delete(`/api/user_to_function_in_units/${item.id}`)
      await load()
    },
  })
}

onMounted(load)
</script>
