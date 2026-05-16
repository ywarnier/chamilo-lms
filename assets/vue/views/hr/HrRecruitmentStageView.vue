<template>
  <div class="space-y-6">
    <SectionHeader :title="t('Recruitment stages')">
      <BaseButton
        :label="t('Add stage')"
        icon="plus"
        type="success"
        @click="openForm()"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="loading"
      :values="stages"
    >
      <Column
        :header="t('Order')"
        field="displayOrder"
        sortable
      />
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
      v-model:is-visible="formDialog"
      :style="{ width: '480px' }"
      :title="editing ? t('Edit stage') : t('Add stage')"
    >
      <div class="space-y-4 pt-2">
        <BaseInputText
          id="stage-title"
          v-model="form.title"
          :label="t('Title')"
          name="title"
        />
        <BaseInputNumber
          id="stage-order"
          v-model="form.displayOrder"
          :label="t('Display order')"
          :min="0"
          :step="1"
          name="display_order"
        />
        <BaseTextArea
          id="stage-description"
          v-model="form.description"
          label="Description"
          name="description"
          rows="3"
        />
      </div>
      <template #footer>
        <BaseButton
          :disabled="!form.title"
          :label="t('Save')"
          icon="save"
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
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import * as stageService from "../../services/hr/recruitmentStageService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const stages = ref([])
const loading = ref(true)
const formDialog = ref(false)
const editing = ref(null)
const form = ref({ title: "", displayOrder: 0, description: "" })

async function load() {
  loading.value = true

  try {
    stages.value = await stageService.getAll()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function openForm(item = null) {
  editing.value = item
  form.value = {
    title: item?.title ?? "",
    displayOrder: item?.displayOrder ?? 0,
    description: item?.description ?? "",
  }
  formDialog.value = true
}

async function save() {
  try {
    const payload = {
      title: form.value.title,
      displayOrder: form.value.displayOrder,
      description: form.value.description || null,
    }

    if (editing.value) {
      await stageService.update(editing.value["@id"], payload)
    } else {
      await stageService.create(payload)
    }

    formDialog.value = false
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
        await stageService.remove(item["@id"])
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
