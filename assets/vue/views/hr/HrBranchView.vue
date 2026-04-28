<template>
  <div class="p-4">
    <SectionHeader :title="t('Branches')">
      <BaseButton
        :label="t('Add branch')"
        icon="plus-box"
        type="success"
        @click="openForm()"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="isLoading"
      :values="branches"
    >
      <Column
        :header="t('Title')"
        field="title"
        sortable
      />
      <Column :header="t('Address')">
        <template #body="{ data }">
          {{ data.address || "—" }}
        </template>
      </Column>
      <Column :header="t('Geographic zone')">
        <template #body="{ data }">
          {{ data.geographicZone ? data.geographicZone.title : "—" }}
        </template>
      </Column>
      <Column :header="t('Coordinates')">
        <template #body="{ data }">
          <span v-if="data.latitude && data.longitude">{{ data.latitude }}, {{ data.longitude }}</span>
          <span v-else>—</span>
        </template>
      </Column>
      <Column :exportable="false">
        <template #body="{ data }">
          <div class="flex justify-end gap-2">
            <BaseButton
              :label="t('Edit')"
              icon="pencil"
              only-icon
              size="small"
              type="secondary-text"
              @click="openForm(data)"
            />
            <BaseButton
              :label="t('Delete')"
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
      v-model:is-visible="dialog"
      :style="{ width: '480px' }"
      :title="editing ? t('Edit branch') : t('Add branch')"
    >
      <BaseInputText
        id="branch-title"
        v-model="form.title"
        :label="t('Title')"
        name="branch_title"
      />
      <BaseTextArea
        id="branch-address"
        v-model="form.address"
        label="Address"
        name="branch_address"
        rows="2"
      />
      <BaseSelect
        id="branch-zone"
        v-model="form.geographicZone"
        :label="t('Geographic zone')"
        :options="zoneOptions"
        allow-cleared
        name="branch_zone"
      />
      <div class="flex gap-4">
        <div class="flex-1">
          <BaseInputNumber
            id="branch-latitude"
            v-model="form.latitude"
            :label="t('Latitude')"
            :step="0.000001"
            name="branch_latitude"
          />
        </div>
        <div class="flex-1">
          <BaseInputNumber
            id="branch-longitude"
            v-model="form.longitude"
            :label="t('Longitude')"
            :step="0.000001"
            name="branch_longitude"
          />
        </div>
      </div>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="dialog = false"
        />
        <BaseButton
          :disabled="!form.title"
          :label="t('Save')"
          icon="content-save"
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
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import baseService from "../../services/baseService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const branches = ref([])
const zones = ref([])
const isLoading = ref(true)
const dialog = ref(false)
const editing = ref(null)
const form = ref({ title: "", address: "", geographicZone: null, latitude: null, longitude: null })

const zoneOptions = computed(() => zones.value.map((z) => ({ label: z.title, value: z["@id"] })))

async function load() {
  isLoading.value = true
  try {
    const [branchResult, zoneResult] = await Promise.all([
      baseService.getCollection("/hr/branches-data", { pagination: false }),
      baseService.getCollection("/api/geographic_zones", { pagination: false }),
    ])
    branches.value = branchResult.items
    zones.value = zoneResult.items
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

function openForm(item = null) {
  editing.value = item
  form.value = {
    title: item ? item.title : "",
    address: item ? (item.address ?? "") : "",
    geographicZone: item && item.geographicZone ? item.geographicZone["@id"] : null,
    latitude: item?.latitude ?? null,
    longitude: item?.longitude ?? null,
  }
  dialog.value = true
}

async function save() {
  const payload = {
    title: form.value.title,
    address: form.value.address || "",
    geographicZone: form.value.geographicZone,
    latitude: form.value.latitude ?? null,
    longitude: form.value.longitude ?? null,
  }
  try {
    if (editing.value) {
      await baseService.put("/hr/branches-data/" + editing.value.id, payload)
    } else {
      await baseService.post("/hr/branches-data", payload, true)
    }
    dialog.value = false
    showSuccessNotification(t("Saved"))
    await load()
  } catch (e) {
    showErrorNotification(e)
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await baseService.delete("/hr/branches-data/" + item.id)
        showSuccessNotification(t("Deleted"))
        await load()
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

onMounted(load)
</script>
