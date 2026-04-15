<template>
  <div class="p-6 space-y-8">
    <!-- Activity Categories -->
    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-700">{{ t("Activity categories") }}</h2>
        <BaseButton
          :label="t('Add category')"
          icon="plus"
          type="success"
          @click="openCategoryForm()"
        />
      </div>

      <BaseTable
        :values="categories"
        :is-loading="categoriesLoading"
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
        <Column :exportable="false">
          <template #body="{ data }">
            <div class="flex justify-end gap-2">
              <BaseButton
                :label="t('Edit')"
                icon="pencil"
                only-icon
                size="small"
                type="secondary-text"
                @click="openCategoryForm(data)"
              />
              <BaseButton
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeleteCategory(data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </section>

    <!-- Activities -->
    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-700">{{ t("Activities") }}</h2>
        <BaseButton
          :label="t('Add activity')"
          icon="plus"
          type="success"
          @click="openActivityForm()"
        />
      </div>

      <BaseTable
        :values="activities"
        :is-loading="activitiesLoading"
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
        <Column :header="t('Category')">
          <template #body="{ data }">
            {{ data.category ? data.category.title : "—" }}
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
                @click="openActivityForm(data)"
              />
              <BaseButton
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeleteActivity(data)"
              />
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
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Title") }}</label>
          <input
            v-model="categoryForm.title"
            name="category_title"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Description") }}</label>
          <input
            v-model="categoryForm.description"
            name="category_description"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
      </div>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          type="plain"
          @click="categoryDialog = false"
        />
        <BaseButton
          :label="t('Save')"
          type="success"
          :disabled="!categoryForm.title"
          @click="saveCategory"
        />
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
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Title") }}</label>
          <input
            v-model="activityForm.title"
            name="activity_title"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Description") }}</label>
          <input
            v-model="activityForm.description"
            name="activity_description"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Category") }}</label>
          <select
            v-model="activityForm.category"
            name="activity_category"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option :value="null">{{ t("None") }}</option>
            <option
              v-for="cat in categories"
              :key="cat['@id']"
              :value="cat['@id']"
            >
              {{ cat.title }}
            </option>
          </select>
        </div>
      </div>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          type="plain"
          @click="activityDialog = false"
        />
        <BaseButton
          :label="t('Save')"
          type="success"
          :disabled="!activityForm.title"
          @click="saveActivity"
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

const categories = ref([])
const categoriesLoading = ref(true)
const activities = ref([])
const activitiesLoading = ref(true)

const categoryDialog = ref(false)
const activityDialog = ref(false)

const editingCategory = ref(null)
const editingActivity = ref(null)

const categoryForm = ref({ title: "", description: "" })
const activityForm = ref({ title: "", description: "", category: null })

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
  categoryForm.value = { title: item ? item.title : "", description: item ? (item.description ?? "") : "" }
  categoryDialog.value = true
}

function openActivityForm(item = null) {
  editingActivity.value = item
  activityForm.value = {
    title: item ? item.title : "",
    description: item ? (item.description ?? "") : "",
    category: item && item.category ? item.category["@id"] : null,
  }
  activityDialog.value = true
}

async function saveCategory() {
  const payload = { title: categoryForm.value.title, description: categoryForm.value.description || null }
  try {
    if (editingCategory.value) {
      await baseService.put(editingCategory.value["@id"], payload)
    } else {
      await baseService.post("/api/activity_categories", payload, true)
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
    title: activityForm.value.title,
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
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await baseService.delete(item["@id"])
        toast.add({ severity: "success", detail: t("Deleted"), life: 3000 })
        await loadCategories()
      } catch (e) {
        toast.add({ severity: "error", detail: e.message, life: 5000 })
      }
    },
  })
}

function confirmDeleteActivity(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await baseService.delete(item["@id"])
        toast.add({ severity: "success", detail: t("Deleted"), life: 3000 })
        await loadActivities()
      } catch (e) {
        toast.add({ severity: "error", detail: e.message, life: 5000 })
      }
    },
  })
}

onMounted(async () => {
  await loadCategories()
  await loadActivities()
})
</script>
