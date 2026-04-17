<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-700">{{ t("Branches") }}</h2>
      <BaseButton
        :label="t('Add branch')"
        icon="plus"
        type="success"
        @click="openForm()"
      />
    </div>

    <BaseTable
      :values="branches"
      :is-loading="isLoading"
    >
      <Column
        field="title"
        :header="t('Title')"
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

    <Dialog
      v-model:visible="dialog"
      :header="editing ? t('Edit branch') : t('Add branch')"
      :modal="true"
      :style="{ width: '480px' }"
    >
      <div class="space-y-4 pt-2">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Title") }}</label>
          <input
            v-model="form.title"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
            name="branch_title"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Address") }}</label>
          <textarea
            v-model="form.address"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            rows="2"
            name="branch_address"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Geographic zone") }}</label>
          <select
            v-model="form.geographicZone"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            name="branch_zone"
          >
            <option :value="null">{{ t("None") }}</option>
            <option
              v-for="zone in zones"
              :key="zone['@id']"
              :value="zone['@id']"
            >
              {{ zone.title }}
            </option>
          </select>
        </div>
        <div class="flex gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Latitude") }}</label>
            <input
              v-model="form.latitude"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
              type="number"
              step="any"
              name="branch_latitude"
            />
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Longitude") }}</label>
            <input
              v-model="form.longitude"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
              type="number"
              step="any"
              name="branch_longitude"
            />
          </div>
        </div>
      </div>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          type="plain"
          @click="dialog = false"
        />
        <BaseButton
          :label="t('Save')"
          type="success"
          :disabled="!form.title"
          @click="save"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import Dialog from "primevue/dialog"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const toast = useToast()
const { requireConfirmation } = useConfirmation()

const branches = ref([])
const zones = ref([])
const isLoading = ref(true)
const dialog = ref(false)
const editing = ref(null)
const form = ref({ title: "", address: "", geographicZone: null, latitude: "", longitude: "" })

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
    latitude: item ? (item.latitude ?? "") : "",
    longitude: item ? (item.longitude ?? "") : "",
  }
  dialog.value = true
}

async function save() {
  const payload = {
    title: form.value.title,
    address: form.value.address || "",
    geographicZone: form.value.geographicZone,
    latitude: form.value.latitude || null,
    longitude: form.value.longitude || null,
  }
  try {
    if (editing.value) {
      await baseService.put("/hr/branches-data/" + editing.value.id, payload)
    } else {
      await baseService.post("/hr/branches-data", payload, true)
    }
    dialog.value = false
    toast.add({ severity: "success", detail: t("Saved"), life: 3000 })
    await load()
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 5000 })
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await baseService.delete("/hr/branches-data/" + item.id)
        toast.add({ severity: "success", detail: t("Deleted"), life: 3000 })
        await load()
      } catch (e) {
        toast.add({ severity: "error", detail: e.message, life: 5000 })
      }
    },
  })
}

onMounted(load)
</script>
