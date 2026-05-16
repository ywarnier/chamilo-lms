<template>
  <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
    <div
      v-if="loading"
      class="text-center py-16 text-gray-400"
    >
      {{ t("Loading") }}…
    </div>

    <template v-else-if="offer">
      <SectionHeader :title="offer.title">
        <BaseButton
          :label="t('Back')"
          :route="{ name: 'JobOfferPublicList' }"
          icon="arrow-left"
          type="plain"
        />
      </SectionHeader>
      <p class="text-sm text-gray-500 -mt-4">
        {{ offer.functionInUnitTitle }}
      </p>

      <div class="grid grid-cols-2 gap-4 text-sm">
        <div
          v-if="offer.salary"
          class="bg-gray-50 rounded p-3"
        >
          <span class="font-medium block text-gray-700">{{ t("Salary") }}</span>
          <span class="text-gray-900">{{ offer.salary }}</span>
        </div>
        <div class="bg-gray-50 rounded p-3">
          <span class="font-medium block text-gray-700">{{ t("Vacancies") }}</span>
          <span class="text-gray-900">{{ offer.vacancy }}</span>
        </div>
        <div
          v-if="offer.expectedStartDate"
          class="bg-gray-50 rounded p-3"
        >
          <span class="font-medium block text-gray-700">{{ t("Expected start date") }}</span>
          <span class="text-gray-900">{{ offer.expectedStartDate.slice(0, 10) }}</span>
        </div>
        <div
          v-if="offer.contractTypeTitle"
          class="bg-gray-50 rounded p-3"
        >
          <span class="font-medium block text-gray-700">{{ t("Contract type") }}</span>
          <span class="text-gray-900">{{ offer.contractTypeTitle }}</span>
        </div>
        <div
          v-if="offer.contractDuration"
          class="bg-gray-50 rounded p-3"
        >
          <span class="font-medium block text-gray-700">{{ t("Contract duration (days)") }}</span>
          <span class="text-gray-900">{{ offer.contractDuration }}</span>
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

      <div class="flex justify-end pt-4">
        <BaseButton
          v-if="isAuthenticated"
          :disabled="alreadyApplied"
          :label="alreadyApplied ? t('Already applied') : t('Apply now')"
          icon="send"
          type="success"
          @click="openApply"
        />
        <BaseButton
          v-else
          :label="t('Log in to apply')"
          :route="{ name: 'Login' }"
          icon="login"
          type="primary"
        />
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
        <BaseSelect
          id="apply-cv"
          v-model="applyForm.cvFile"
          :label="t('CV (Personal file)')"
          :options="personalFileOptions"
          allow-clear
          name="cv_file"
        />
        <BaseSelect
          id="apply-motivation"
          v-model="applyForm.motivationLetterFile"
          :label="t('Motivation letter (Personal file)')"
          :options="personalFileOptions"
          allow-clear
          name="motivation_letter_file"
        />
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
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
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
const personalFileOptions = ref([])
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
      alreadyApplied.value = myApps.some((a) => a.jobOffer === `/api/job_offers/${offerId}`)
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function openApply() {
  try {
    const res = await axios.get("/api/personal_files?pagination=false")
    personalFileOptions.value = (res.data["hydra:member"] ?? []).map((f) => ({
      label: f.title,
      value: f["@id"],
    }))
  } catch (e) {
    console.error(e)
  }
  applyForm.value = {
    introduction: "",
    salaryExpectations: "",
    availabilityDate: null,
    cvFile: null,
    motivationLetterFile: null,
  }
  applyDialog.value = true
}

async function submitApplication() {
  try {
    await jobOfferApplicationService.create({
      jobOffer: offer.value["@id"],
      introduction: applyForm.value.introduction || null,
      salaryExpectations: applyForm.value.salaryExpectations || null,
      availabilityDate: applyForm.value.availabilityDate ? applyForm.value.availabilityDate.toISOString() : null,
      cvFile: applyForm.value.cvFile || null,
      motivationLetterFile: applyForm.value.motivationLetterFile || null,
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
