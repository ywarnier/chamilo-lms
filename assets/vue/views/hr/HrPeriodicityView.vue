<template>
  <div>
    <SectionHeader :title="t('Periodicities')">
      <BaseButton
        :label="t('Add periodicity')"
        icon="plus-box"
        type="success"
        @click="openForm(null)"
      />
    </SectionHeader>

    <BaseTable
      v-model:rows="pageSize"
      :is-loading="loading"
      :lazy="true"
      :total-items="total"
      :values="periodicities"
      data-key="id"
      @page="onPage"
    >
      <Column
        :header="t('Title')"
        field="title"
      />
      <Column
        :header="t('Days')"
        field="days"
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
      v-model:is-visible="showDialog"
      :title="form.id ? t('Edit periodicity') : t('Add periodicity')"
    >
      <form
        class="space-y-4"
        @submit.prevent="save"
      >
        <BaseInputText
          id="periodicity-title"
          v-model="form.title"
          :label="t('Title')"
          name="title"
          required
        />
        <BaseInputText
          id="periodicity-days"
          v-model="form.days"
          :label="t('Days')"
          name="days"
          required
          type="number"
        />
      </form>
      <template #footer>
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
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import baseService from "../../services/baseService"
import * as periodicityService from "../../services/hr/periodicityService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const periodicities = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = ref(20)
const loading = ref(false)
const saving = ref(false)
const showDialog = ref(false)

const emptyForm = () => ({ iri: null, title: "", days: "" })
const form = ref(emptyForm())

async function load() {
  loading.value = true
  try {
    const { items, totalItems } = await baseService.getCollection("/api/periodicities", {
      page: page.value,
      itemsPerPage: pageSize.value,
    })
    periodicities.value = items
    total.value = totalItems
  } catch {
    showErrorNotification(t("Could not load periodicities"))
  } finally {
    loading.value = false
  }
}

function onPage(event) {
  page.value = event.page + 1
  pageSize.value = event.rows
  load()
}

function openForm(item) {
  form.value = item ? { iri: item["@id"], title: item.title, days: item.days } : emptyForm()
  showDialog.value = true
}

async function save() {
  if (!form.value.title || !form.value.days) return
  saving.value = true
  try {
    const payload = { title: form.value.title, days: Number(form.value.days) }
    if (form.value.iri) {
      await periodicityService.update(form.value.iri, payload)
    } else {
      await periodicityService.create(payload)
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
    message: t("Are you sure you want to delete this periodicity?"),
    accept: () => doDelete(item),
  })
}

async function doDelete(item) {
  try {
    await periodicityService.remove(item["@id"])
    showSuccessNotification(t("Deleted"))
    await load()
  } catch {
    showErrorNotification(t("Could not delete"))
  }
}

onMounted(load)
</script>
