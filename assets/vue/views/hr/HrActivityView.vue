<template>
  <div class="p-6 space-y-8">
    <!-- Activity Categories -->
    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-700">{{ t("Activity categories") }}</h2>
        <button
          class="btn btn--success"
          @click="openCategoryForm()"
        >
          <span class="mdi mdi-plus-box ch-tool-icon" />
          {{ t("Add category") }}
        </button>
      </div>

      <BaseTable
        :values="categories"
        :is-loading="categoriesLoading"
      >
        <Column
          field="name"
          :header="t('Name')"
          sortable
        />
        <Column :exportable="false">
          <template #body="{ data }">
            <div class="flex justify-end gap-2">
              <button
                class="btn btn--secondary btn--sm"
                @click="openCategoryForm(data)"
              >
                <span class="mdi mdi-pencil ch-tool-icon" />
              </button>
              <button
                class="btn btn--danger btn--sm"
                @click="confirmDeleteCategory(data)"
              >
                <span class="mdi mdi-delete ch-tool-icon" />
              </button>
            </div>
          </template>
        </Column>
      </BaseTable>
    </section>

    <!-- Activities -->
    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-700">{{ t("Activities") }}</h2>
        <button
          class="btn btn--success"
          @click="openActivityForm()"
        >
          <span class="mdi mdi-plus-box ch-tool-icon" />
          {{ t("Add activity") }}
        </button>
      </div>

      <BaseTable
        :values="activities"
        :is-loading="activitiesLoading"
      >
        <Column
          field="name"
          :header="t('Name')"
          sortable
        />
        <Column :header="t('Description')">
          <template #body="{ data }">
            {{ data.description || "—" }}
          </template>
        </Column>
        <Column :header="t('Category')">
          <template #body="{ data }">
            {{ data.category ? data.category.name : "—" }}
          </template>
        </Column>
        <Column :exportable="false">
          <template #body="{ data }">
            <div class="flex justify-end gap-2">
              <button
                class="btn btn--secondary btn--sm"
                @click="openActivityForm(data)"
              >
                <span class="mdi mdi-pencil ch-tool-icon" />
              </button>
              <button
                class="btn btn--danger btn--sm"
                @click="confirmDeleteActivity(data)"
              >
                <span class="mdi mdi-delete ch-tool-icon" />
              </button>
            </div>
          </template>
        </Column>
      </BaseTable>
    </section>

    <!-- Category form dialog -->
    <Dialog
      v-model:visible="categoryDialog"
      :header="editingCategory ? t('Edit category') : t('Add category')"
      :modal="true"
      :style="{ width: '420px' }"
    >
      <div class="space-y-4 pt-2">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Name") }}</label>
          <input
            v-model="categoryForm.name"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
      </div>
      <template #footer>
        <button
          class="btn btn--plain"
          @click="categoryDialog = false"
        >
          {{ t("Cancel") }}
        </button>
        <button
          class="btn btn--success"
          :disabled="!categoryForm.name"
          @click="saveCategory"
        >
          {{ t("Save") }}
        </button>
      </template>
    </Dialog>

    <!-- Activity form dialog -->
    <Dialog
      v-model:visible="activityDialog"
      :header="editingActivity ? t('Edit activity') : t('Add activity')"
      :modal="true"
      :style="{ width: '480px' }"
    >
      <div class="space-y-4 pt-2">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Name") }}</label>
          <input
            v-model="activityForm.name"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Description") }}</label>
          <input
            v-model="activityForm.description"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Category") }}</label>
          <select
            v-model="activityForm.category"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option :value="null">{{ t("None") }}</option>
            <option
              v-for="cat in categories"
              :key="cat['@id']"
              :value="cat['@id']"
            >
              {{ cat.name }}
            </option>
          </select>
        </div>
      </div>
      <template #footer>
        <button
          class="btn btn--plain"
          @click="activityDialog = false"
        >
          {{ t("Cancel") }}
        </button>
        <button
          class="btn btn--success"
          :disabled="!activityForm.name"
          @click="saveActivity"
        >
          {{ t("Save") }}
        </button>
      </template>
    </Dialog>

    <!-- Delete confirmation dialog -->
    <Dialog
      v-model:visible="deleteDialog"
      :header="t('Confirm')"
      :modal="true"
      :style="{ width: '400px' }"
    >
      <div class="flex items-center gap-3">
        <span class="mdi mdi-alert-circle-outline text-red-500 text-3xl" />
        <span>{{ t("Are you sure you want to delete this item?") }}</span>
      </div>
      <template #footer>
        <button
          class="btn btn--plain"
          @click="deleteDialog = false"
        >
          {{ t("Cancel") }}
        </button>
        <button
          class="btn btn--danger"
          @click="performDelete"
        >
          {{ t("Delete") }}
        </button>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import Dialog from "primevue/dialog"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"

const { t } = useI18n()
const toast = useToast()

const categories = ref([])
const categoriesLoading = ref(true)
const activities = ref([])
const activitiesLoading = ref(true)

const categoryDialog = ref(false)
const activityDialog = ref(false)
const deleteDialog = ref(false)

const editingCategory = ref(null)
const editingActivity = ref(null)
const pendingDelete = ref(null)

const categoryForm = ref({ name: "" })
const activityForm = ref({ name: "", description: "", category: null })

async function loadCategories() {
  categoriesLoading.value = true
  try {
    const result = await baseService.getCollection("/api/activity_categories", { pagination: false })
    categories.value = result.items
  } catch (e) {
    console.error(e)
  } finally {
    categoriesLoading.value = false
  }
}

async function loadActivities() {
  activitiesLoading.value = true
  try {
    const result = await baseService.getCollection("/api/activities", { pagination: false })
    activities.value = result.items
  } catch (e) {
    console.error(e)
  } finally {
    activitiesLoading.value = false
  }
}

function openCategoryForm(item = null) {
  editingCategory.value = item
  categoryForm.value = { name: item ? item.name : "" }
  categoryDialog.value = true
}

function openActivityForm(item = null) {
  editingActivity.value = item
  activityForm.value = {
    name: item ? item.name : "",
    description: item ? (item.description ?? "") : "",
    category: item && item.category ? item.category["@id"] : null,
  }
  activityDialog.value = true
}

async function saveCategory() {
  try {
    if (editingCategory.value) {
      await baseService.put(editingCategory.value["@id"], { name: categoryForm.value.name })
    } else {
      await baseService.post("/api/activity_categories", { name: categoryForm.value.name }, true)
    }
    categoryDialog.value = false
    toast.add({ severity: "success", detail: t("Saved"), life: 3000 })
    await loadCategories()
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 5000 })
  }
}

async function saveActivity() {
  const payload = {
    name: activityForm.value.name,
    description: activityForm.value.description || null,
    category: activityForm.value.category,
  }
  try {
    if (editingActivity.value) {
      await baseService.put(editingActivity.value["@id"], payload)
    } else {
      await baseService.post("/api/activities", payload, true)
    }
    activityDialog.value = false
    toast.add({ severity: "success", detail: t("Saved"), life: 3000 })
    await loadActivities()
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 5000 })
  }
}

function confirmDeleteCategory(item) {
  pendingDelete.value = { type: "category", item }
  deleteDialog.value = true
}

function confirmDeleteActivity(item) {
  pendingDelete.value = { type: "activity", item }
  deleteDialog.value = true
}

async function performDelete() {
  deleteDialog.value = false
  try {
    await baseService.delete(pendingDelete.value.item["@id"])
    toast.add({ severity: "success", detail: t("Deleted"), life: 3000 })
    if ("category" === pendingDelete.value.type) {
      await loadCategories()
    } else {
      await loadActivities()
    }
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 5000 })
  }
}

onMounted(async () => {
  await loadCategories()
  await loadActivities()
})
</script>
