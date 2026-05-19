<template>
  <div class="space-y-6">
    <SectionHeader :title="offer ? `${t('Applications')}: ${offer.title}` : t('Applications')">
      <BaseButton
        :label="t('Calculate quiz scores')"
        :loading="computingScores"
        icon="calculator"
        type="secondary"
        @click="computeScores"
      />
      <BaseButton
        :label="t('Back to job offers')"
        :route="{ name: 'HrJobOffers' }"
        icon="arrow-left"
        type="plain"
      />
    </SectionHeader>

    <div
      v-if="!loading && activeApplications.length && averageScore !== null"
      class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 rounded px-4 py-2"
    >
      <span class="mdi mdi-chart-bar text-blue-500" />
      {{ t("Average score") }}: <strong>{{ averageScore }}%</strong>
      <span class="text-gray-400">({{ scoredCount }}/{{ activeApplications.length }} {{ t("evaluated") }})</span>
    </div>

    <BaseTable
      :is-loading="loading"
      :values="applications"
    >
      <Column :header="t('Applicant')">
        <template #body="{ data }">
          {{ data.applicantName }}
        </template>
      </Column>
      <Column :header="t('Applied on')">
        <template #body="{ data }">
          {{ data.createdAt.slice(0, 10) }}
        </template>
      </Column>
      <Column :header="t('Salary expectations')">
        <template #body="{ data }">
          {{ data.salaryExpectations || "—" }}
        </template>
      </Column>
      <Column :header="t('Score')">
        <template #body="{ data }">
          {{ data.totalScore !== null ? `${data.totalScore}%` : "—" }}
        </template>
      </Column>
      <Column :header="t('Status')">
        <template #body="{ data }">
          <span
            v-if="data.withdrawnAt"
            class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500"
          >
            {{ t("Withdrawn by candidate") }}
          </span>
          <span
            v-else
            :class="statusClass(data.hired)"
            class="px-2 py-0.5 rounded text-xs font-medium"
          >
            {{ statusLabel(data.hired) }}
          </span>
        </template>
      </Column>
      <Column :exportable="false">
        <template #body="{ data }">
          <div class="flex justify-end gap-2">
            <BaseButton
              :label="t('View candidate')"
              :route="{ name: 'HrJobOfferCandidate', params: { id: route.params.id, applicationId: data.id } }"
              icon="view-candidate"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="isAdmin"
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
import axios from "axios"
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import * as jobOfferService from "../../services/hr/jobOfferService"
import * as jobOfferApplicationService from "../../services/hr/jobOfferApplicationService"

const STATUS_HIRED = 1
const STATUS_NOT_HIRED = 2

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()
const route = useRoute()
const securityStore = useSecurityStore()
const isAdmin = computed(() => securityStore.isAdmin)

const offer = ref(null)
const applications = ref([])
const loading = ref(true)
const computingScores = ref(false)

const activeApplications = computed(() => applications.value.filter((a) => !a.withdrawnAt))
const scoredCount = computed(() => activeApplications.value.filter((a) => a.totalScore !== null).length)
const averageScore = computed(() => {
  if (0 === scoredCount.value) return null
  const sum = activeApplications.value.reduce((acc, a) => acc + (a.totalScore ?? 0), 0)
  return Math.round((sum / scoredCount.value) * 10) / 10
})

function statusLabel(hired) {
  if (STATUS_HIRED === hired) return t("Hired")
  if (STATUS_NOT_HIRED === hired) return t("Not hired")
  return t("Pending evaluation")
}

function statusClass(hired) {
  if (STATUS_HIRED === hired) return "bg-green-100 text-green-700"
  if (STATUS_NOT_HIRED === hired) return "bg-red-100 text-red-700"
  return "bg-blue-100 text-blue-700"
}

async function computeScores() {
  computingScores.value = true
  try {
    const offerId = route.params.id
    await axios.post(`/api/job_offers/${offerId}/compute_scores`)
    showSuccessNotification(t("Scores calculated."))
    await load()
  } catch (e) {
    showErrorNotification(e)
  } finally {
    computingScores.value = false
  }
}

async function load() {
  loading.value = true
  try {
    const offerId = route.params.id
    const [offerData, appData] = await Promise.all([
      jobOfferService.getOne(`/api/job_offers/${offerId}`),
      jobOfferApplicationService.getByJobOffer(offerId),
    ])
    offer.value = offerData
    applications.value = appData.sort((a, b) => (a.withdrawnAt ? 1 : 0) - (b.withdrawnAt ? 1 : 0))
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await jobOfferApplicationService.remove(item["@id"])
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
