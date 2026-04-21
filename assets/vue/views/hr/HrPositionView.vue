<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ t("Positions") }}</h1>
      <BaseButton
        type="success"
        icon="plus-box"
        :label="t('Add position')"
        @click="openCreate"
      />
    </div>

    <BaseTable :values="items" :is-loading="isLoading">
      <Column field="userFullName" :header="t('Staff member')" sortable />
      <Column field="userUsername" :header="t('Username')" />
      <Column field="functionInUnitTitle" :header="t('Position title')" sortable />
      <Column field="businessUnitTitle" :header="t('Business unit')" sortable />
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
          <span v-if="data.isBoss" class="text-green-600 font-semibold">✓</span>
        </template>
      </Column>
      <Column :header="t('Actions')" style="width: 100px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <BaseButton type="secondary-text" icon="pencil" only-icon size="small" @click="openEdit(data)" />
            <BaseButton type="danger-text" icon="delete" only-icon size="small" @click="confirmDelete(data)" />
          </div>
        </template>
      </Column>
    </BaseTable>

    <Dialog
      v-model:visible="dialogVisible"
      :header="editingItem ? t('Edit position') : t('Add position')"
      modal
      :style="{ width: '560px' }"
    >
      <div class="flex flex-col gap-3 pt-2">
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Staff member") }} *</label>
          <select
            v-model="form.user"
            name="position_user"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option value="">— {{ t("Select") }} —</option>
            <option v-for="u in users" :key="u['@id']" :value="u['@id']">
              {{ u.fullName }} ({{ u.username }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Function-unit association") }} *</label>
          <select
            v-model="form.functionInUnit"
            name="position_function_in_unit"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option value="">— {{ t("Select") }} —</option>
            <option v-for="fiu in functionInUnits" :key="fiu['@id']" :value="fiu['@id']">
              {{ fiu.title }} — {{ fiu.businessUnitTitle }}
            </option>
          </select>
        </div>
        <div class="flex gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1">{{ t("Start date") }} *</label>
            <input
              v-model="form.startDate"
              name="position_start_date"
              type="date"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            />
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1">{{ t("End date") }}</label>
            <input
              v-model="form.endDate"
              name="position_end_date"
              type="date"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Branch") }}</label>
          <select
            v-model="form.branch"
            name="position_branch"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option :value="null">— {{ t("None") }} —</option>
            <option v-for="b in branches" :key="b['@id']" :value="b['@id']">{{ b.title }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Geographic zone") }}</label>
          <select
            v-model="form.geographicZone"
            name="position_geographic_zone"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option :value="null">— {{ t("None") }} —</option>
            <option v-for="gz in geographicZones" :key="gz['@id']" :value="gz['@id']">
              {{ gz.title }}
            </option>
          </select>
        </div>
        <div class="flex gap-4 items-center">
          <div class="flex-1">
            <label class="block text-sm font-medium mb-1">{{ t("FTE (0–1)") }}</label>
            <input
              v-model="form.contractEtp"
              name="position_contract_etp"
              type="number"
              min="0"
              max="1"
              step="0.01"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            />
          </div>
          <div class="flex items-center gap-2 mt-5">
            <input
              id="position_is_boss"
              v-model="form.isBoss"
              name="position_is_boss"
              type="checkbox"
              class="rounded"
            />
            <label for="position_is_boss" class="text-sm font-medium">{{ t("Is manager/boss") }}</label>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="flex gap-2 justify-end">
          <BaseButton type="plain" :label="t('Cancel')" @click="dialogVisible = false" />
          <BaseButton type="success" :label="t('Save')" :disabled="isSaving" @click="save" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import Dialog from "primevue/dialog"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
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

const emptyForm = () => ({
  user: "", functionInUnit: "", startDate: "", endDate: "",
  branch: null, geographicZone: null, contractEtp: 1, isBoss: false,
})
const form = ref(emptyForm())

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
  dialogVisible.value = true
}

function openEdit(item) {
  editingItem.value = item
  form.value = {
    user: item.userIri ?? "",
    functionInUnit: item.functionInUnitIri ?? "",
    startDate: item.startDate,
    endDate: item.endDate ?? "",
    branch: null,
    geographicZone: null,
    contractEtp: item.contractEtp ?? 1,
    isBoss: item.isBoss,
  }
  dialogVisible.value = true
}

async function save() {
  if (!form.value.user || !form.value.functionInUnit || !form.value.startDate) return
  isSaving.value = true
  try {
    const payload = {
      user: form.value.user,
      functionInUnit: form.value.functionInUnit,
      startDate: form.value.startDate ? new Date(form.value.startDate).toISOString() : null,
      endDate: form.value.endDate ? new Date(form.value.endDate).toISOString() : null,
      branch: form.value.branch || null,
      geographicZone: form.value.geographicZone || null,
      contractEtp: form.value.contractEtp ? String(parseFloat(form.value.contractEtp)) : null,
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
