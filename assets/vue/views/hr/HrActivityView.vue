<template>
  <div class="activity-page">
    <!-- Activity Categories -->
    <section>
      <SectionHeader :title="t('Activity categories')">
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

    <!-- Activities -->
    <section>
      <SectionHeader :title="t('Activities')">
        <BaseButton
          :label="t('Add activity')"
          icon="plus"
          type="success"
          @click="openActivityForm()"
        />
      </SectionHeader>

      <BaseTable
        :is-loading="activitiesLoading"
        :values="activities"
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
                @click="openActivityForm(data)"
              />
              <BaseButton
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
          :disabled="!categoryForm.title"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveCategory"
        />
      </template>
    </BaseDialog>

    <!-- Activity form dialog -->
    <BaseDialog
      v-model:is-visible="activityDialog"
      :style="{ width: '480px' }"
      :title="editingActivity ? t('Edit activity') : t('Add activity')"
    >
      <BaseInputText
        id="activity-title"
        v-model="activityForm.title"
        :label="t('Title')"
        name="activity_title"
      />
      <BaseInputText
        id="activity-description"
        v-model="activityForm.description"
        :label="t('Description')"
        name="activity_description"
      />
      <BaseSelect
        id="activity-category"
        v-model="activityForm.category"
        :label="t('Category')"
        :options="categoryOptions"
        allow-cleared
        name="activity_category"
      />

      <template #footer>
        <BaseButton
          :disabled="!activityForm.title"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveActivity"
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
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import baseService from "../../services/baseService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
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

const categoryOptions = computed(() => categories.value.map((cat) => ({ label: cat.title, value: cat["@id"] })))

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
    showSuccessNotification(t("Saved"))
    await loadCategories()
  } catch (e) {
    showErrorNotification(e)
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
    showSuccessNotification(t("Saved"))
    await loadActivities()
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

function confirmDeleteActivity(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await baseService.delete(item["@id"])
        showSuccessNotification(t("Deleted"))
        await loadActivities()
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

onMounted(async () => {
  await loadCategories()
  await loadActivities()
})
</script>
