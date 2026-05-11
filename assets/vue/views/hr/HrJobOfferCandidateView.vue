<template>
  <div class="space-y-6">
    <SectionHeader :title="application ? application.applicantName : t('Candidate')">
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-500 mr-1"> {{ currentIndex + 1 }}/{{ applications.length }} </span>
        <BaseButton
          :disabled="currentIndex === 0"
          :label="t('Previous')"
          :route="prevRoute"
          icon="chevron-left"
          only-icon
          type="secondary"
        />
        <BaseButton
          :disabled="currentIndex >= applications.length - 1"
          :label="t('Next')"
          :route="nextRoute"
          icon="chevron-right"
          only-icon
          type="secondary"
        />
        <BaseButton
          :label="t('Back to list')"
          :route="{ name: 'HrJobOfferApplications', params: { id: offerId } }"
          icon="arrow-left"
          type="plain"
        />
      </div>
    </SectionHeader>

    <div
      v-if="loading"
      class="text-center py-16 text-gray-400"
    >
      {{ t("Loading") }}…
    </div>

    <template v-else-if="application">
      <!-- Application summary -->
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div class="bg-gray-50 rounded p-3">
          <span class="font-medium block text-gray-700">{{ t("Applied on") }}</span>
          <span>{{ application.createdAt.slice(0, 10) }}</span>
        </div>
        <div class="bg-gray-50 rounded p-3">
          <span class="font-medium block text-gray-700">{{ t("Score") }}</span>
          <span>{{ application.totalScore !== null ? `${application.totalScore}%` : "—" }}</span>
        </div>
        <div
          v-if="application.salaryExpectations"
          class="bg-gray-50 rounded p-3"
        >
          <span class="font-medium block text-gray-700">{{ t("Salary expectations") }}</span>
          <span>{{ application.salaryExpectations }}</span>
        </div>
        <div
          v-if="application.availabilityDate"
          class="bg-gray-50 rounded p-3"
        >
          <span class="font-medium block text-gray-700">{{ t("Availability date") }}</span>
          <span>{{ application.availabilityDate.slice(0, 10) }}</span>
        </div>
        <div
          v-if="application.introduction"
          class="bg-gray-50 rounded p-3 col-span-2"
        >
          <span class="font-medium block text-gray-700">{{ t("Introduction") }}</span>
          <span>{{ application.introduction }}</span>
        </div>
        <div
          v-if="application.firstEvaluatedAt"
          class="bg-gray-50 rounded p-3"
        >
          <span class="font-medium block text-gray-700">{{ t("First evaluated on") }}</span>
          <span>{{ application.firstEvaluatedAt.slice(0, 10) }}</span>
        </div>
        <div
          v-if="application.evaluatedByName"
          class="bg-gray-50 rounded p-3"
        >
          <span class="font-medium block text-gray-700">{{ t("Evaluated by") }}</span>
          <span>{{ application.evaluatedByName }}</span>
        </div>
      </div>

      <!-- Files -->
      <div class="flex flex-wrap gap-3">
        <BaseButton
          v-if="application.cvFileTitle"
          :is-loading="loadingCvUrl"
          :label="application.cvFileTitle"
          icon="file-account"
          type="primary"
          @click="downloadFile('cv')"
        />
        <BaseButton
          v-if="application.motivationLetterFileTitle"
          :is-loading="loadingMotivationUrl"
          :label="application.motivationLetterFileTitle"
          icon="file-document"
          type="primary"
          @click="downloadFile('motivation')"
        />
      </div>

      <!-- Skills comparison -->
      <div v-if="requiredSkills.length">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">
          {{ t("Required skills") }}
        </h3>
        <BaseTable :values="requiredSkills">
          <Column :header="t('Skill')">
            <template #body="{ data }">
              {{ data.skillTitle }}
            </template>
          </Column>
          <Column :header="t('Required level')">
            <template #body="{ data }">
              {{ data.levelTitle || "—" }}
            </template>
          </Column>
          <Column :header="t('Candidate has it')">
            <template #body="{ data }">
              <span
                v-if="candidateHasSkill(data.skillId)"
                class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700"
              >
                ✓ {{ t("Yes") }}
                <span
                  v-if="candidateSkillLevel(data.skillId)"
                  class="ml-1"
                >
                  ({{ candidateSkillLevel(data.skillId) }})
                </span>
              </span>
              <span
                v-else
                class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700"
              >
                ✗ {{ t("No") }}
              </span>
            </template>
          </Column>
        </BaseTable>
      </div>

      <!-- Evaluation -->
      <Fieldset :legend="t('Evaluation')">
        <div class="space-y-6 pt-2">
          <div class="flex gap-6 items-start">
            <BaseRadioButtons
              v-model="evalForm.hired"
              :options="hiredOptions"
              :title="t('Decision')"
              name="hired"
            />
            <BaseInputNumber
              id="eval-score"
              v-model="evalForm.totalScore"
              :label="t('Score')"
              :min="0"
              :step="0.1"
              name="total_score"
            />
          </div>

          <Fieldset :legend="t('CV')">
            <div class="space-y-3 pt-1">
              <BaseTextArea
                id="eval-cv-obs"
                v-model="evalForm.cvObservation"
                label="Notes (visible to candidate)"
                name="cv_observation"
                rows="2"
              />
              <BaseTextArea
                id="eval-cv-priv"
                v-model="evalForm.cvPrivateObservation"
                label="Private notes"
                name="cv_private_observation"
                rows="2"
              />
            </div>
          </Fieldset>

          <Fieldset :legend="t('Motivation letter')">
            <div class="space-y-3 pt-1">
              <BaseTextArea
                id="eval-ml-obs"
                v-model="evalForm.motivationLetterObservation"
                label="Notes (visible to candidate)"
                name="motivation_letter_observation"
                rows="2"
              />
              <BaseTextArea
                id="eval-ml-priv"
                v-model="evalForm.motivationLetterPrivateObservation"
                label="Private notes"
                name="motivation_letter_private_observation"
                rows="2"
              />
            </div>
          </Fieldset>

          <Fieldset :legend="t('Interview')">
            <div class="space-y-3 pt-1">
              <BaseTextArea
                id="eval-observation"
                v-model="evalForm.observation"
                label="Notes (visible to candidate)"
                name="observation"
                rows="2"
              />
              <BaseTextArea
                id="eval-private"
                v-model="evalForm.privateObservation"
                label="Private notes"
                name="private_observation"
                rows="2"
              />
            </div>
          </Fieldset>

          <div class="flex justify-end">
            <BaseButton
              :label="t('Save evaluation')"
              icon="save"
              type="success"
              @click="saveEvaluation"
            />
          </div>
        </div>
      </Fieldset>
    </template>
  </div>
</template>

<script setup>
import axios from "axios"
import Fieldset from "primevue/fieldset"
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import * as jobOfferApplicationService from "../../services/hr/jobOfferApplicationService"

const STATUS_STAND_BY = 0
const STATUS_HIRED = 1
const STATUS_NOT_HIRED = 2

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const route = useRoute()

const offerId = computed(() => route.params.id)
const applicationId = computed(() => route.params.applicationId)

const loading = ref(true)
const application = ref(null)
const applications = ref([])

const requiredSkills = ref([])
const candidateSkills = ref([])

const loadingCvUrl = ref(false)
const loadingMotivationUrl = ref(false)

const evalForm = ref({
  hired: STATUS_STAND_BY,
  totalScore: null,
  cvObservation: "",
  cvPrivateObservation: "",
  motivationLetterObservation: "",
  motivationLetterPrivateObservation: "",
  observation: "",
  privateObservation: "",
})

const hiredOptions = [
  { label: t("Pending evaluation"), value: STATUS_STAND_BY },
  { label: t("Hired"), value: STATUS_HIRED },
  { label: t("Not hired"), value: STATUS_NOT_HIRED },
]

const currentIndex = computed(() => applications.value.findIndex((a) => String(a.id) === String(applicationId.value)))

const prevRoute = computed(() => {
  const idx = currentIndex.value
  if (idx <= 0) return null
  return { name: "HrJobOfferCandidate", params: { id: offerId.value, applicationId: applications.value[idx - 1].id } }
})

const nextRoute = computed(() => {
  const idx = currentIndex.value
  if (idx >= applications.value.length - 1) return null
  return { name: "HrJobOfferCandidate", params: { id: offerId.value, applicationId: applications.value[idx + 1].id } }
})

function candidateHasSkill(skillId) {
  return candidateSkills.value.some((s) => s.skillId === skillId)
}

function candidateSkillLevel(skillId) {
  return candidateSkills.value.find((s) => s.skillId === skillId)?.levelTitle ?? null
}

async function load() {
  loading.value = true
  try {
    const [appData, allApps] = await Promise.all([
      jobOfferApplicationService.getOne(`/api/job_offer_applications/${applicationId.value}`),
      jobOfferApplicationService.getByJobOffer(offerId.value),
    ])
    application.value = appData
    applications.value = allApps

    evalForm.value = {
      hired: appData.hired,
      totalScore: appData.totalScore ?? null,
      cvObservation: appData.cvObservation ?? "",
      cvPrivateObservation: appData.cvPrivateObservation ?? "",
      motivationLetterObservation: appData.motivationLetterObservation ?? "",
      motivationLetterPrivateObservation: appData.motivationLetterPrivateObservation ?? "",
      observation: appData.observation ?? "",
      privateObservation: appData.privateObservation ?? "",
    }

    const [skillsRes, candidateRes] = await Promise.all([
      loadFunctionSkills(appData),
      axios.get(`/hr/user-skills/${appData.createdById}`),
    ])
    requiredSkills.value = skillsRes
    candidateSkills.value = candidateRes.data ?? []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function loadFunctionSkills(app) {
  try {
    const offerRes = await axios.get(app.jobOffer)
    const fiuIri = offerRes.data?.functionInUnit
    if (!fiuIri) return []
    const fiuId = fiuIri.split("/").pop()
    const res = await axios.get(`/hr/function-required-skills/${fiuId}`)
    return res.data ?? []
  } catch {
    return []
  }
}

async function downloadFile(type) {
  const loadingRef = "cv" === type ? loadingCvUrl : loadingMotivationUrl
  loadingRef.value = true
  try {
    const res = await axios.get(`/hr/job-offer-application/${applicationId.value}/file-info/${type}`)
    if (res.data?.url) {
      window.open(res.data.url, "_blank")
    }
  } catch (e) {
    showErrorNotification(e)
  } finally {
    loadingRef.value = false
  }
}

async function saveEvaluation() {
  try {
    await jobOfferApplicationService.update(`/api/job_offer_applications/${applicationId.value}`, {
      hired: evalForm.value.hired,
      totalScore: evalForm.value.totalScore,
      cvObservation: evalForm.value.cvObservation || null,
      cvPrivateObservation: evalForm.value.cvPrivateObservation || null,
      motivationLetterObservation: evalForm.value.motivationLetterObservation || null,
      motivationLetterPrivateObservation: evalForm.value.motivationLetterPrivateObservation || null,
      observation: evalForm.value.observation || null,
      privateObservation: evalForm.value.privateObservation || null,
    })
    showSuccessNotification(t("Saved"))
    application.value = { ...application.value, ...evalForm.value }
  } catch (e) {
    showErrorNotification(e)
  }
}

watch(applicationId, load)
onMounted(load)
</script>
