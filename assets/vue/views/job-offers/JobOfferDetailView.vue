<template>
  <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
    <div
      v-if="loading"
      class="text-center py-16 text-gray-400"
    >
      {{ t("Loading") }}…
    </div>

    <template v-else-if="offer">
      <div>
        <BaseButton
          :label="t('Back')"
          :route="{ name: 'JobOfferPublicList' }"
          icon="back"
          only-icon
          type="plain"
        />
      </div>

      <div class="border-b border-gray-200 pb-4">
        <h1 class="text-2xl font-bold text-gray-900">
          {{ offer.title }}
        </h1>
        <p
          v-if="offer.functionInUnitTitle"
          class="mt-1 text-sm text-gray-500"
        >
          {{ offer.functionInUnitTitle }}
        </p>
      </div>

      <div class="grid grid-cols-2 gap-4 text-sm">
        <div
          v-if="offer.salary"
          class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
        >
          <span class="font-semibold block text-gray-500 text-xs uppercase tracking-wide mb-1">{{ t("Salary") }}</span>
          <span class="text-gray-900 font-medium">{{ offer.salary }}</span>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
          <span class="font-semibold block text-gray-500 text-xs uppercase tracking-wide mb-1">{{ t("Vacancies") }}</span>
          <span class="text-gray-900 font-medium">{{ offer.vacancy }}</span>
        </div>
        <div
          v-if="offer.expectedStartDate"
          class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
        >
          <span class="font-semibold block text-gray-500 text-xs uppercase tracking-wide mb-1">{{ t("Expected start date") }}</span>
          <span class="text-gray-900 font-medium">{{ offer.expectedStartDate.slice(0, 10) }}</span>
        </div>
        <div
          v-if="offer.contractTypeTitle"
          class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
        >
          <span class="font-semibold block text-gray-500 text-xs uppercase tracking-wide mb-1">{{ t("Contract type") }}</span>
          <span class="text-gray-900 font-medium">{{ offer.contractTypeTitle }}</span>
        </div>
        <div
          v-if="offer.contractDuration"
          class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
        >
          <span class="font-semibold block text-gray-500 text-xs uppercase tracking-wide mb-1">{{ t("Contract duration (days)") }}</span>
          <span class="text-gray-900 font-medium">{{ offer.contractDuration }}</span>
        </div>
      </div>

      <div
        class="prose max-w-none text-gray-700"
        v-html="offer.description"
      />

      <div v-if="offer.requiredSkills && offer.requiredSkills.length">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">
          {{ t("Required skills") }}
        </h3>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="skill in offer.requiredSkills"
            :key="skill.skillId"
            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-blue-50 text-blue-700 border border-blue-200"
          >
            {{ skill.skillTitle }}
            <span
              v-if="skill.levelTitle"
              class="text-blue-500 text-xs"
              >({{ skill.levelTitle }})</span
            >
          </span>
        </div>
      </div>

      <div v-if="offer.selectionTests && offer.selectionTests.length">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">
          {{ t("Selection tests") }}
        </h3>
        <ul class="space-y-1">
          <li
            v-for="test in offer.selectionTests"
            :key="test.exerciseUrl"
            class="flex items-center gap-2 text-sm text-gray-700"
          >
            <span class="mdi mdi-quiz text-blue-500" />
            {{ test.title }}
            <span class="text-gray-400">({{ test.courseTitle }})</span>
          </li>
        </ul>
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
        <BaseButton
          v-if="isAuthenticated"
          :disabled="alreadyApplied"
          :label="alreadyApplied ? t('Already applied') : t('Apply now')"
          icon="send"
          type="success"
          @click="openApply"
        />
        <template v-else>
          <BaseButton
            :label="t('Register to apply')"
            :to-url="'/registration'"
            icon="account-plus"
            type="success"
          />
          <BaseButton
            :label="t('Log in to apply')"
            :route="{ name: 'Login' }"
            icon="login"
            type="primary"
          />
        </template>
      </div>
    </template>

    <!-- Apply dialog -->
    <BaseDialog
      v-model:is-visible="applyDialog"
      :style="{ width: '560px' }"
      :title="t('Apply to job offer')"
    >
      <div class="space-y-4 pt-2">
        <BaseTextArea
          id="apply-intro"
          v-model="applyForm.introduction"
          label="Introduction"
          name="introduction"
          rows="3"
        />
        <BaseInputText
          id="apply-salary"
          v-model="applyForm.salaryExpectations"
          :label="t('Salary expectations')"
          name="salary_expectations"
        />
        <BaseCalendar
          id="apply-availability"
          v-model="applyForm.availabilityDate"
          :label="t('Availability date')"
        />
        <div>
          <label
            class="block text-sm font-medium text-gray-700 mb-1"
            for="apply-cv"
          >
            {{ t("CV") }}
          </label>
          <input
            id="apply-cv"
            class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-1.5 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            name="cv_file"
            type="file"
            @change="applyForm.cvFile = $event.target.files[0] ?? null"
          />
        </div>
        <div>
          <label
            class="block text-sm font-medium text-gray-700 mb-1"
            for="apply-motivation"
          >
            {{ t("Motivation letter") }}
          </label>
          <input
            id="apply-motivation"
            class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-1.5 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            name="motivation_letter_file"
            type="file"
            @change="applyForm.motivationLetterFile = $event.target.files[0] ?? null"
          />
        </div>
      </div>
      <template #footer>
        <BaseButton
          :label="t('Submit application')"
          icon="send"
          type="success"
          @click="submitApplication"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import axios from "axios"
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import { useNotification } from "../../composables/notification"
import * as jobOfferService from "../../services/hr/jobOfferService"
import * as jobOfferApplicationService from "../../services/hr/jobOfferApplicationService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const route = useRoute()
const securityStore = useSecurityStore()

const offer = ref(null)
const loading = ref(true)
const alreadyApplied = ref(false)
const isAuthenticated = computed(() => securityStore.isAuthenticated)

const applyDialog = ref(false)
const applyForm = ref({
  introduction: "",
  salaryExpectations: "",
  availabilityDate: null,
  cvFile: null,
  motivationLetterFile: null,
})

async function load() {
  loading.value = true
  try {
    const offerId = route.params.id
    offer.value = await jobOfferService.getOne(`/api/job_offers/${offerId}`)

    if (isAuthenticated.value) {
      const myApps = await jobOfferApplicationService.getMine()
      alreadyApplied.value = myApps.some((a) => a.jobOffer === `/api/job_offers/${offerId}` && !a.withdrawnAt)
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function openApply() {
  applyForm.value = {
    introduction: "",
    salaryExpectations: "",
    availabilityDate: null,
    cvFile: null,
    motivationLetterFile: null,
  }
  applyDialog.value = true
}

async function uploadPersonalFile(file) {
  const nodeId = securityStore.user?.resourceNode?.id
  const formData = new FormData()
  formData.append("uploadFile", file)
  formData.append("title", file.name)
  formData.append("filetype", "file")
  formData.append("parentResourceNodeId", String(nodeId))
  formData.append("parentResourceNode", `/api/resource_nodes/${nodeId}`)
  const res = await axios.post("/api/personal_files", formData)
  return res.data["@id"]
}

async function submitApplication() {
  try {
    const [cvIri, motivationIri] = await Promise.all([
      applyForm.value.cvFile ? uploadPersonalFile(applyForm.value.cvFile) : null,
      applyForm.value.motivationLetterFile ? uploadPersonalFile(applyForm.value.motivationLetterFile) : null,
    ])
    await jobOfferApplicationService.create({
      jobOffer: offer.value["@id"],
      introduction: applyForm.value.introduction || null,
      salaryExpectations: applyForm.value.salaryExpectations || null,
      availabilityDate: applyForm.value.availabilityDate ? applyForm.value.availabilityDate.toISOString() : null,
      cvFile: cvIri,
      motivationLetterFile: motivationIri,
    })
    applyDialog.value = false
    alreadyApplied.value = true
    showSuccessNotification(t("Application submitted successfully."))
  } catch (e) {
    showErrorNotification(e)
  }
}

onMounted(load)
</script>
