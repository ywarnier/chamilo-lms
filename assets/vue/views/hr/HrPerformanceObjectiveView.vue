<template>
  <div class="performance-objective-page">
    <!-- Objective Categories -->
    <section>
      <SectionHeader :title="t('Objective categories')">
        <BaseButton
          :label="t('Add category')"
          icon="plus"
          type="success"
          @click="openCategoryForm()"
        />
      </SectionHeader>

      <BaseTable
        :is-loading="categoriesLoading"
        :values="categories"
      >
        <Column
          :header="t('Title')"
          field="title"
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
                icon="pencil"
                only-icon
                size="small"
                type="secondary-text"
                @click="openCategoryForm(data)"
              />
              <BaseButton
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
      <SectionHeader :title="t('Performance objectives')">
        <BaseButton
          :label="t('Add objective')"
          icon="plus"
          type="success"
          @click="openObjectiveForm()"
        />
      </SectionHeader>

      <BaseTable
        :is-loading="objectivesLoading"
        :values="objectives"
      >
        <Column
          :header="t('Title')"
          field="title"
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
                icon="pencil"
                only-icon
                size="small"
                type="secondary-text"
                @click="openObjectiveForm(data)"
              />
              <BaseButton
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
    <BaseDialog
      v-model:is-visible="categoryDialog"
      :style="{ width: '420px' }"
      :title="editingCategory ? t('Edit category') : t('Add category')"
    >
      <BaseInputText
        id="category-title"
        v-model="categoryForm.title"
        :label="t('Title')"
        name="category_title"
      />
      <BaseInputText
        id="category-description"
        v-model="categoryForm.description"
        :label="t('Description')"
        name="category_description"
      />

      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="categoryDialog = false"
        />
        <BaseButton
          :disabled="!categoryForm.title"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveCategory"
        />
      </template>
    </BaseDialog>

    <!-- Objective form dialog -->
    <BaseDialog
      v-model:is-visible="objectiveDialog"
      :style="{ width: '480px' }"
      :title="editingObjective ? t('Edit objective') : t('Add objective')"
    >
      <BaseInputText
        id="objective-title"
        v-model="objectiveForm.title"
        :label="t('Title')"
        name="objective_title"
      />
      <BaseTextArea
        id="objective-description"
        v-model="objectiveForm.description"
        label="Description"
        name="objective_description"
        rows="3"
      />
      <BaseSelect
        id="objective-category"
        v-model="objectiveForm.category"
        :label="t('Category')"
        :options="categoryOptions"
        allow-cleared
        name="objective_category"
      />

      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="objectiveDialog = false"
        />
        <BaseButton
          :disabled="!objectiveForm.title"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveObjective"
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

const categories = ref([])
const categoriesLoading = ref(true)
const objectives = ref([])
const objectivesLoading = ref(true)

const categoryDialog = ref(false)
const objectiveDialog = ref(false)

const editingCategory = ref(null)
const editingObjective = ref(null)

const categoryForm = ref({ title: "", description: "" })
const objectiveForm = ref({ title: "", description: "", category: null })

const categoryOptions = computed(() => categories.value.map((cat) => ({ label: cat.title, value: cat["@id"] })))

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
    showSuccessNotification(t("Saved"))
    await loadCategories()
  } catch (e) {
    showErrorNotification(e)
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
    showSuccessNotification(t("Saved"))
    await loadObjectives()
  } catch (e) {
    showErrorNotification(e)
  }
}

function confirmDeleteCategory(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await baseService.delete(item["@id"])
        showSuccessNotification(t("Deleted"))
        await loadCategories()
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

function confirmDeleteObjective(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await baseService.delete(item["@id"])
        showSuccessNotification(t("Deleted"))
        await loadObjectives()
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

onMounted(async () => {
  await loadCategories()
  await loadObjectives()
})
</script>
