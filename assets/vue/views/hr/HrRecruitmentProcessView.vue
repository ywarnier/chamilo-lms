<template>
  <div class="space-y-6">
    <SectionHeader :title="t('Recruitment processes')">
      <BaseButton
        :label="t('Add process')"
        icon="plus"
        type="success"
        @click="openForm()"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="loading"
      :values="processes"
    >
      <Column :header="t('Job offer')">
        <template #body="{ data }">
          {{ data.jobOfferTitle }}
        </template>
      </Column>
      <Column :header="t('Candidate')">
        <template #body="{ data }">
          {{ data.applicantName }}
        </template>
      </Column>
      <Column :header="t('Created by')">
        <template #body="{ data }">
          {{ data.createdByName }}
        </template>
      </Column>
      <Column :header="t('Date')">
        <template #body="{ data }">
          {{ data.createdAt.slice(0, 10) }}
        </template>
      </Column>
      <Column :exportable="false">
        <template #body="{ data }">
          <div class="flex justify-end gap-2">
            <BaseButton
              :label="t('View process')"
              :route="{ name: 'HrRecruitmentProcessDetail', params: { id: data.id } }"
              icon="format-list-checks"
              only-icon
              size="small"
              type="secondary-text"
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
      :style="{ width: '520px' }"
      :title="t('Add recruitment process')"
    >
      <div class="space-y-4 pt-2">
        <BaseSelect
          id="proc-offer"
          v-model="form.jobOffer"
          :label="t('Job offer')"
          :options="offerOptions"
          name="job_offer"
        />
        <BaseSelect
          id="proc-application"
          v-model="form.application"
          :is-loading="loadingApplications"
          :label="t('Candidate')"
          :options="applicationOptions"
          name="application"
        />
        <BaseTextArea
          id="proc-notes"
          v-model="form.notes"
          label="Notes"
          name="notes"
          rows="3"
        />
      </div>
      <template #footer>
        <BaseButton
          :disabled="!form.jobOffer || !form.application"
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
import { onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import * as processService from "../../services/hr/recruitmentProcessService"
import * as jobOfferService from "../../services/hr/jobOfferService"
import * as jobOfferApplicationService from "../../services/hr/jobOfferApplicationService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const processes = ref([])
const loading = ref(true)
const formDialog = ref(false)
const form = ref({ jobOffer: null, application: null, notes: "" })

const offerOptions = ref([])
const applicationOptions = ref([])
const loadingApplications = ref(false)

async function load() {
  loading.value = true

  try {
    processes.value = await processService.getAll()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function openForm() {
  form.value = { jobOffer: null, application: null, notes: "" }
  applicationOptions.value = []

  try {
    const offers = await jobOfferService.getAll()
    offerOptions.value = offers.map((o) => ({ label: o.title, value: o["@id"] }))
  } catch (e) {
    console.error(e)
  }

  formDialog.value = true
}

watch(
  () => form.value.jobOffer,
  async (iri) => {
    applicationOptions.value = []
    form.value.application = null

    if (!iri) {
      return
    }

    loadingApplications.value = true

    try {
      const id = iri.split("/").pop()
      const apps = await jobOfferApplicationService.getByJobOffer(id)

      applicationOptions.value = apps.map((a) => ({ label: a.applicantName, value: a["@id"] }))
    } catch (e) {
      console.error(e)
    } finally {
      loadingApplications.value = false
    }
  },
)

async function save() {
  try {
    await processService.create({
      jobOffer: form.value.jobOffer,
      application: form.value.application,
      notes: form.value.notes || null,
    })

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
        await processService.remove(item["@id"])
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
