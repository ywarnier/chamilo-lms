<template>
  <div class="staff-status-page">
    <SectionHeader :title="t('Staff statuses')">
      <BaseButton
        :label="t('Add staff status')"
        icon="plus"
        type="success"
        @click="openForm()"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="isLoading"
      :values="items"
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
      :style="{ width: '440px' }"
      :title="editing ? t('Edit staff status') : t('Add staff status')"
    >
      <BaseInputText
        id="status-title"
        v-model="form.title"
        :label="t('Title')"
        name="status_title"
      />
      <BaseTextArea
        id="status-description"
        v-model="form.description"
        label="Description"
        name="status_description"
        rows="3"
      />

      <template #footer>
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
import { onMounted, ref } from "vue"
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

const items = ref([])
const isLoading = ref(true)
const dialog = ref(false)
const editing = ref(null)
const form = ref({ title: "", description: "" })

async function load() {
  isLoading.value = true
  try {
    const result = await baseService.getCollection("/api/staff_statuses", { pagination: false })
    items.value = result.items
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

function openForm(item = null) {
  editing.value = item
  form.value = { title: item ? item.title : "", description: item ? (item.description ?? "") : "" }
  dialog.value = true
}

async function save() {
  const payload = { title: form.value.title, description: form.value.description || null }
  try {
    if (editing.value) {
      await baseService.put(editing.value["@id"], payload)
    } else {
      await baseService.post("/api/staff_statuses", payload, true)
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
        await baseService.delete(item["@id"])
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
