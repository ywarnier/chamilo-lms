<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-700">{{ t("Business units") }}</h2>
      <BaseButton
        :label="t('Add business unit')"
        icon="plus"
        type="success"
        @click="openForm()"
      />
    </div>

    <BaseTable
      :values="items"
      :is-loading="isLoading"
    >
      <Column
        field="title"
        :header="t('Title')"
        sortable
      />
      <Column :header="t('Description')">
        <template #body="{ data }">
          {{ data.description || "—" }}
        </template>
      </Column>
      <Column :header="t('Parent unit')">
        <template #body="{ data }">
          {{ data.parentTitle || "—" }}
        </template>
      </Column>
      <Column :header="t('Primary branch')">
        <template #body="{ data }">
          {{ data.branchTitle || "—" }}
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
      :header="editing ? t('Edit business unit') : t('Add business unit')"
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
            name="unit_title"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Description") }}</label>
          <textarea
            v-model="form.description"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            rows="2"
            name="unit_description"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Parent unit") }}</label>
          <select
            v-model="form.parent"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            name="unit_parent"
          >
            <option :value="null">{{ t("None") }}</option>
            <option
              v-for="unit in parentOptions"
              :key="unit['@id']"
              :value="unit['@id']"
            >
              {{ unit.title }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Primary branch") }}</label>
          <select
            v-model="form.branch"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            name="unit_branch"
          >
            <option :value="null">{{ t("None") }}</option>
            <option
              v-for="branch in branches"
              :key="branch['@id']"
              :value="branch['@id']"
            >
              {{ branch.title }}
            </option>
          </select>
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
import { computed, onMounted, ref } from "vue"
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

const items = ref([])
const branches = ref([])
const isLoading = ref(true)
const dialog = ref(false)
const editing = ref(null)
const form = ref({ title: "", description: "", parent: null, branch: null })

const parentOptions = computed(() =>
  editing.value ? items.value.filter((u) => u["@id"] !== editing.value["@id"]) : items.value,
)

async function load() {
  isLoading.value = true
  try {
    const [unitResult, branchResult] = await Promise.all([
      baseService.getCollection("/api/business_units", { pagination: false }),
      baseService.getCollection("/hr/branches-data", { pagination: false }),
    ])
    items.value = unitResult.items
    branches.value = branchResult.items
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
    description: item ? (item.description ?? "") : "",
    parent: item ? (item.parentIri ?? null) : null,
    branch: item ? (item.branchIri ?? null) : null,
  }
  dialog.value = true
}

async function save() {
  const payload = {
    title: form.value.title,
    description: form.value.description || null,
    parent: form.value.parent,
    branch: form.value.branch,
  }
  try {
    if (editing.value) {
      await baseService.put(editing.value["@id"], payload)
    } else {
      await baseService.post("/api/business_units", payload, true)
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
        await baseService.delete(item["@id"])
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
