<template>
  <div>
    <SectionHeader :title="t('Evaluation stages')">
      <BaseButton
        :label="t('Add stage')"
        icon="plus-box"
        type="success"
        @click="openForm(null)"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="loading"
      :values="stages"
    >
      <Column
        :header="t('Title')"
        field="title"
        sortable
      />
      <Column
        :header="t('Description')"
        field="description"
        sortable
      />
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
      v-if="showDialog"
      :title="form.id ? t('Edit stage') : t('Add stage')"
      :visible="showDialog"
      @close="showDialog = false"
    >
      <form
        class="space-y-4"
        @submit.prevent="save"
      >
        <BaseInputText
          id="stage-title"
          v-model="form.title"
          :label="t('Title')"
          name="title"
          required
        />
        <BaseTextArea
          id="stage-description"
          v-model="form.description"
          :label="t('Description')"
          name="description"
        />
      </form>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          type="plain"
          @click="showDialog = false"
        />
        <BaseButton
          :label="t('Save')"
          :loading="saving"
          icon="content-save"
          type="success"
          @click="save"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import baseService from "../../services/baseService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const stages = ref([])
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)

const emptyForm = () => ({ id: null, title: "", description: "" })
const form = ref(emptyForm())

async function load() {
  loading.value = true
  try {
    const res = await baseService.get("/api/recruitment_stages")
    stages.value = res["hydra:member"] ?? res
  } catch {
    showErrorNotification(t("Could not load evaluation stages"))
  } finally {
    loading.value = false
  }
}

function openForm(item) {
  form.value = item
    ? { id: item.id, title: item.title, description: item.description ?? "" }
    : emptyForm()
  showDialog.value = true
}

async function save() {
  if (!form.value.title) return
  saving.value = true
  try {
    const payload = { title: form.value.title, description: form.value.description || null }
    if (form.value.id) {
      await baseService.put(`/api/recruitment_stages/${form.value.id}`, payload)
    } else {
      await baseService.post("/api/recruitment_stages", payload)
    }
    showSuccessNotification(t("Saved"))
    showDialog.value = false
    await load()
  } catch {
    showErrorNotification(t("Could not save"))
  } finally {
    saving.value = false
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this stage?"),
    accept: () => doDelete(item),
  })
}

async function doDelete(item) {
  try {
    await baseService.delete(`/api/recruitment_stages/${item.id}`)
    showSuccessNotification(t("Deleted"))
    await load()
  } catch {
    showErrorNotification(t("Could not delete"))
  }
}

onMounted(load)
</script>
