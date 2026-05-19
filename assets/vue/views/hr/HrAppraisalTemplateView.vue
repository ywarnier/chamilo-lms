<template>
  <div>
    <SectionHeader :title="t('Evaluation templates')">
      <BaseButton
        :label="t('Add template')"
        icon="plus-box"
        type="success"
        @click="goToEdit(null)"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="loading"
      :values="templates"
    >
      <Column
        :header="t('Title')"
        field="title"
        sortable
      />
      <Column :header="t('Periodicity')">
        <template #body="{ data }">
          {{ data.periodicity ? data.periodicity.title : "—" }}
        </template>
      </Column>
      <Column :header="t('Items')">
        <template #body="{ data }">
          {{ data.items ? data.items.length : 0 }}
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
              @click="goToEdit(data)"
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
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import baseService from "../../services/baseService"

const { t } = useI18n()
const router = useRouter()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const templates = ref([])
const loading = ref(false)

async function load() {
  loading.value = true
  try {
    const { items } = await baseService.getCollection("/api/performance_appraisal_templates", { pagination: false })
    templates.value = items
  } catch {
    showErrorNotification(t("Could not load templates"))
  } finally {
    loading.value = false
  }
}

function goToEdit(item) {
  router.push({
    name: "HrAppraisalTemplateEdit",
    params: { id: item ? item.id : undefined },
  })
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this template?"),
    accept: () => doDelete(item),
  })
}

async function doDelete(item) {
  try {
    await baseService.delete(`/api/performance_appraisal_templates/${item.id}`)
    showSuccessNotification(t("Deleted"))
    await load()
  } catch {
    showErrorNotification(t("Could not delete"))
  }
}

onMounted(load)
</script>
