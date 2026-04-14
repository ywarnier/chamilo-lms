<template>
  <div class="p-6 space-y-8">
    <!-- Objective Categories -->
    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-700">{{ t("Objective categories") }}</h2>
        <button
          class="btn btn--success"
          @click="openCategoryForm()"
        >
          <span class="mdi mdi-plus-box" />
          {{ t("Add category") }}
        </button>
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

    <!-- Performance Objectives -->
    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-700">{{ t("Performance objectives") }}</h2>
        <button
          class="btn btn--success"
          @click="openObjectiveForm()"
        >
          <span class="mdi mdi-plus-box" />
          {{ t("Add objective") }}
        </button>
      </div>

      <BaseTable
        :values="objectives"
        :is-loading="objectivesLoading"
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
                @click="openObjectiveForm(data)"
              />
              <BaseButton
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeleteObjective(data)"
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
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Description") }}</label>
          <input
            v-model="categoryForm.description"
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
          :disabled="!categoryForm.title"
          @click="saveCategory"
        >
          {{ t("Save") }}
        </button>
      </template>
    </Dialog>

    <!-- Objective form dialog -->
    <Dialog
      v-model:visible="objectiveDialog"
      :header="editingObjective ? t('Edit objective') : t('Add objective')"
      :modal="true"
      :style="{ width: '480px' }"
    >
      <div class="space-y-4 pt-2">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Title") }}</label>
          <input
            v-model="objectiveForm.title"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Description") }}</label>
          <textarea
            v-model="objectiveForm.description"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            rows="3"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Category") }}</label>
          <select
            v-model="objectiveForm.category"
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
        <button
          class="btn btn--plain"
          @click="objectiveDialog = false"
        >
          {{ t("Cancel") }}
        </button>
        <button
          class="btn btn--success"
          :disabled="!objectiveForm.title"
          @click="saveObjective"
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
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"

const { t } = useI18n()
const toast = useToast()

const categories = ref([])
const categoriesLoading = ref(true)
const objectives = ref([])
const objectivesLoading = ref(true)

const categoryDialog = ref(false)
const objectiveDialog = ref(false)
const deleteDialog = ref(false)

const editingCategory = ref(null)
const editingObjective = ref(null)
const pendingDelete = ref(null)

const categoryForm = ref({ title: "", description: "" })
const objectiveForm = ref({ title: "", description: "", category: null })

async function loadCategories() {
  categoriesLoading.value = true
  try {
    const result = await baseService.getCollection("/api/performance_objective_categories", { pagination: false })
    categories.value = result.items
  } catch (e) {
    console.error(e)
  } finally {
    categoriesLoading.value = false
  }
}

async function loadObjectives() {
  objectivesLoading.value = true
  try {
    const result = await baseService.getCollection("/api/performance_objectives", { pagination: false })
    objectives.value = result.items
  } catch (e) {
    console.error(e)
  } finally {
    objectivesLoading.value = false
  }
}

function openCategoryForm(item = null) {
  editingCategory.value = item
  categoryForm.value = { title: item ? item.title : "", description: item ? (item.description ?? "") : "" }
  categoryDialog.value = true
}

function openObjectiveForm(item = null) {
  editingObjective.value = item
  objectiveForm.value = {
    title: item ? item.title : "",
    description: item ? (item.description ?? "") : "",
    category: item && item.category ? item.category["@id"] : null,
  }
  objectiveDialog.value = true
}

async function saveCategory() {
  const payload = { title: categoryForm.value.title, description: categoryForm.value.description || null }
  try {
    if (editingCategory.value) {
      await baseService.put(editingCategory.value["@id"], payload)
    } else {
      await baseService.post("/api/performance_objective_categories", payload, true)
    }
    categoryDialog.value = false
    toast.add({ severity: "success", detail: t("Saved"), life: 3000 })
    await loadCategories()
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 5000 })
  }
}

async function saveObjective() {
  const payload = {
    title: objectiveForm.value.title,
    description: objectiveForm.value.description || null,
    category: objectiveForm.value.category,
  }
  try {
    if (editingObjective.value) {
      await baseService.put(editingObjective.value["@id"], payload)
    } else {
      await baseService.post("/api/performance_objectives", payload, true)
    }
    objectiveDialog.value = false
    toast.add({ severity: "success", detail: t("Saved"), life: 3000 })
    await loadObjectives()
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 5000 })
  }
}

function confirmDeleteCategory(item) {
  pendingDelete.value = { type: "category", item }
  deleteDialog.value = true
}

function confirmDeleteObjective(item) {
  pendingDelete.value = { type: "objective", item }
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
      await loadObjectives()
    }
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 5000 })
  }
}

onMounted(async () => {
  await loadCategories()
  await loadObjectives()
})
</script>
