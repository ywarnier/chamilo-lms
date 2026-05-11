<template>
  <div class="hr-diversity-criteria">
    <SectionHeader :title="t('Diversity criteria')">
      <BaseButton
        :label="t('Add diversity criterion')"
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
      <Column :header="t('Extra field')">
        <template #body="{ data }">
          {{ data.extraFieldDisplayText || "—" }}
        </template>
      </Column>
      <Column :header="t('Creation date')">
        <template #body="{ data }">
          {{ formatDate(data.createdOn) }}
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
      :style="{ width: '520px' }"
      :title="editing ? t('Edit diversity criterion') : t('Add diversity criterion')"
    >
      <div class="space-y-4 pt-2">
        <BaseInputText
          id="diversity-criteria-title"
          v-model="form.title"
          :label="t('Title')"
          name="diversity_criteria_title"
        />
        <BaseSelect
          id="diversity-criteria-extra-field"
          v-model="form.extraField"
          :label="t('Extra field')"
          :options="extraFieldOptions"
          name="diversity_criteria_extra_field"
          option-label="label"
          option-value="value"
        />
        <div class="flex justify-end -mt-2">
          <BaseButton
            :label="t('Add profile field')"
            icon="file-add"
            size="small"
            to-url="/main/admin/extra_fields.php?type=user&action=add"
            type="secondary-text"
          />
        </div>
        <BaseTextArea
          id="diversity-criteria-description"
          v-model="form.description"
          :rows="3"
          label="Description"
          name="diversity_criteria_description"
        />
      </div>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="dialog = false"
        />
        <BaseButton
          :disabled="!form.title || !form.extraField"
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
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import * as diversityCriteriaService from "../../services/hr/diversityCriteriaService"
import * as extraFieldService from "../../services/extraFieldService"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import {
  EXTRAFIELD_ITEM_USER,
  EXTRAFIELD_VALUE_RADIO,
  EXTRAFIELD_VALUE_SELECT,
} from "../../constants/entity/extrafield"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const items = ref([])
const extraFields = ref([])
const isLoading = ref(true)
const dialog = ref(false)
const editing = ref(null)
const form = ref({ title: "", description: "", extraField: null })

const extraFieldOptions = computed(() =>
  extraFields.value.map((ef) => ({
    label: ef.displayText || ef.variable,
    value: ef["@id"],
  })),
)

function formatDate(dateStr) {
  if (!dateStr) return "—"

  return new Date(dateStr).toLocaleDateString()
}

async function load() {
  isLoading.value = true
  try {
    const [criteriaItems, allExtraFields] = await Promise.all([
      diversityCriteriaService.getAll(),
      extraFieldService.getByItemType(EXTRAFIELD_ITEM_USER),
    ])

    items.value = criteriaItems
    extraFields.value = allExtraFields.filter((ef) =>
      [EXTRAFIELD_VALUE_RADIO, EXTRAFIELD_VALUE_SELECT].includes(ef.valueType),
    )
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

function openForm(item = null) {
  editing.value = item
  form.value = {
    title: item ? item.title : "",
    description: item ? (item.description ?? "") : "",
    extraField: item ? item.extraField["@id"] : null,
  }
  dialog.value = true
}

async function save() {
  const payload = {
    title: form.value.title,
    description: form.value.description || null,
    extraField: form.value.extraField,
  }

  try {
    if (editing.value) {
      await diversityCriteriaService.update(editing.value["@id"], payload)
    } else {
      await diversityCriteriaService.create(payload)
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
        await diversityCriteriaService.remove(item["@id"])

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
