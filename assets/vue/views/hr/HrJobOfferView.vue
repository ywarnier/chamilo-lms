<template>
  <div class="space-y-6">
    <SectionHeader :title="t('Job offers')">
      <BaseButton
        :label="t('Add job offer')"
        icon="plus"
        type="success"
        @click="openForm()"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="loading"
      :values="offers"
    >
      <Column
        :header="t('Title')"
        field="title"
        sortable
      />
      <Column :header="t('Function / Unit')">
        <template #body="{ data }">
          {{ data.functionInUnitTitle }}
        </template>
      </Column>
      <Column
        :header="t('Vacancies')"
        field="vacancy"
      />
      <Column :header="t('Expected start date')">
        <template #body="{ data }">
          {{ data.expectedStartDate ? data.expectedStartDate.slice(0, 10) : "—" }}
        </template>
      </Column>
      <Column :header="t('Status')">
        <template #body="{ data }">
          <span
            :class="data.isPublic ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
            class="px-2 py-0.5 rounded text-xs font-medium"
          >
            {{ data.isPublic ? t("Public") : t("Private") }}
          </span>
        </template>
      </Column>
      <Column :header="t('Applications')">
        <template #body="{ data }">
          {{ data.applicationCount }}
        </template>
      </Column>
      <Column :exportable="false">
        <template #body="{ data }">
          <div class="flex justify-end gap-2">
            <BaseButton
              :label="t('Applications')"
              :route="{ name: 'HrJobOfferApplications', params: { id: data.id } }"
              icon="agenda-list"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              :label="t('Exams')"
              icon="quiz"
              only-icon
              size="small"
              type="secondary-text"
              @click="openQuizDialog(data)"
            />
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

    <!-- Create / Edit job offer dialog -->
    <BaseDialog
      v-model:is-visible="formDialog"
      :title="editing ? t('Edit job offer') : t('Add job offer')"
      :style="{ width: '640px' }"
    >
      <BaseInputText
        id="jo-title"
        v-model="form.title"
        :label="t('Title')"
        name="title"
      />

      <BaseSelect
        id="jo-function"
        v-model="form.functionInUnit"
        :label="t('Function / Unit')"
        :options="functionInUnitOptions"
        name="function_in_unit"
      />

      <BaseTextArea
        id="jo-description"
        v-model="form.description"
        label="Description"
        name="description"
        rows="4"
      />

      <div class="flex gap-4 mb-5">
        <div class="flex-1">
          <BaseInputNumber
            id="jo-vacancy"
            v-model="form.vacancy"
            :label="t('Vacancies')"
            :min="1"
            :step="1"
            name="vacancy"
          />
        </div>
        <div class="flex-1">
          <BaseInputText
            id="jo-salary"
            v-model="form.salary"
            :label="t('Salary')"
            name="salary"
          />
        </div>
      </div>

      <div class="flex gap-4 mb-5">
        <div class="flex-1">
          <BaseCalendar
            id="jo-expected-start"
            v-model="form.expectedStartDate"
            :label="t('Expected start date')"
          />
        </div>
        <div class="flex-1">
          <BaseInputNumber
            id="jo-contract-duration"
            v-model="form.contractDuration"
            :label="t('Contract duration (days)')"
            :min="1"
            :step="1"
            name="contract_duration"
          />
        </div>
      </div>

      <BaseSelect
        id="jo-contract-type"
        v-model="form.contractType"
        :label="t('Contract type')"
        :options="contractTypeOptions"
        allow-clear
        name="contract_type"
      />

      <BaseCheckbox
        id="jo-is-public"
        v-model="form.isPublic"
        :label="t('Public offer')"
        name="is_public"
      />

      <BaseCalendar
        v-if="form.isPublic"
        id="jo-pub-period"
        v-model="form.publicationPeriod"
        :label="t('Publication period')"
        type="range"
      />

      <template #footer>
        <BaseButton
          :disabled="!form.title || !form.functionInUnit || !form.description"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="save"
        />
      </template>
    </BaseDialog>

    <!-- Quiz assignment dialog -->
    <BaseDialog
      v-model:is-visible="quizDialog"
      :close-label="t('Close')"
      :title="t('Selection exams')"
      :style="{ width: '560px' }"
    >
      <div class="space-y-4">
        <BaseTable
          :is-loading="quizzesLoading"
          :values="assignedQuizzes"
        >
          <Column :header="t('Course')">
            <template #body="{ data }">
              {{ data.courseTitle }}
            </template>
          </Column>
          <Column :header="t('Exam')">
            <template #body="{ data }">
              {{ data.cquizTitle }}
            </template>
          </Column>
          <Column :exportable="false">
            <template #body="{ data }">
              <div class="flex justify-end">
                <BaseButton
                  :label="t('Remove')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="removeQuiz(data)"
                />
              </div>
            </template>
          </Column>
        </BaseTable>

        <Fieldset :legend="t('Add exam')">
          <div class="space-y-3">
            <BaseSelect
              id="quiz-course"
              v-model="quizForm.course"
              :label="t('Course')"
              :options="courseOptions"
              name="quiz_course"
              @change="loadCourseQuizzes"
            />
            <BaseSelect
              id="quiz-cquiz"
              v-model="quizForm.cquiz"
              :label="t('Exam')"
              :options="courseQuizOptions"
              name="quiz_cquiz"
            />
            <BaseButton
              :disabled="!quizForm.course || !quizForm.cquiz"
              :label="t('Add exam')"
              icon="plus"
              type="success"
              @click="addQuiz"
            />
          </div>
        </Fieldset>
      </div>
    </BaseDialog>
  </div>
</template>

<script setup>
import axios from "axios"
import Fieldset from "primevue/fieldset"
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import * as jobOfferService from "../../services/hr/jobOfferService"
import * as jobOfferQuizService from "../../services/hr/jobOfferQuizService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const offers = ref([])
const loading = ref(true)

const formDialog = ref(false)
const editing = ref(null)
const form = ref(emptyForm())

const functionInUnitOptions = ref([])
const contractTypeOptions = ref([])

const quizDialog = ref(false)
const quizzesLoading = ref(false)
const activeOffer = ref(null)
const assignedQuizzes = ref([])
const courseOptions = ref([])
const courseQuizOptions = ref([])
const quizForm = ref({ course: null, cquiz: null })

function emptyForm() {
  return {
    title: "",
    description: "",
    functionInUnit: null,
    vacancy: 1,
    salary: "",
    expectedStartDate: null,
    contractDuration: null,
    contractType: null,
    isPublic: false,
    publicationPeriod: null,
  }
}

async function load() {
  loading.value = true
  try {
    offers.value = await jobOfferService.getAll()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function loadSelects() {
  const [fiuRes, ctRes] = await Promise.all([
    axios.get("/api/function_in_units?pagination=false"),
    axios.get("/api/contract_types?pagination=false"),
  ])
  functionInUnitOptions.value = (fiuRes.data["hydra:member"] ?? []).map((f) => ({
    label: `${f.title} — ${f.businessUnitTitle}`,
    value: f["@id"],
  }))
  contractTypeOptions.value = (ctRes.data["hydra:member"] ?? []).map((c) => ({
    label: c.title,
    value: c["@id"],
  }))
}

function openForm(item = null) {
  editing.value = item
  form.value = item
    ? {
        title: item.title,
        description: item.description,
        functionInUnit: item.functionInUnit,
        vacancy: item.vacancy,
        salary: item.salary ?? "",
        expectedStartDate: item.expectedStartDate ? new Date(item.expectedStartDate) : null,
        contractDuration: item.contractDuration ?? null,
        contractType: item.contractType ?? null,
        isPublic: item.isPublic,
        publicationPeriod:
          item.publicationStartDate || item.publicationEndDate
            ? [
                item.publicationStartDate ? new Date(item.publicationStartDate) : null,
                item.publicationEndDate ? new Date(item.publicationEndDate) : null,
              ]
            : null,
      }
    : emptyForm()
  formDialog.value = true
}

function buildPayload() {
  return {
    title: form.value.title,
    description: form.value.description,
    functionInUnit: form.value.functionInUnit,
    vacancy: form.value.vacancy,
    salary: form.value.salary || null,
    expectedStartDate: form.value.expectedStartDate ? form.value.expectedStartDate.toISOString().slice(0, 10) : null,
    contractDuration: form.value.contractDuration || null,
    contractType: form.value.contractType || null,
    isPublic: form.value.isPublic,
    publicationStartDate: form.value.publicationPeriod?.[0]
      ? new Date(form.value.publicationPeriod[0]).toISOString()
      : null,
    publicationEndDate: form.value.publicationPeriod?.[1]
      ? new Date(form.value.publicationPeriod[1]).toISOString()
      : null,
  }
}

async function save() {
  try {
    if (editing.value) {
      await jobOfferService.update(editing.value["@id"], buildPayload())
    } else {
      await jobOfferService.create(buildPayload())
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
        await jobOfferService.remove(item["@id"])
        showSuccessNotification(t("Deleted"))
        await load()
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

async function openQuizDialog(item) {
  activeOffer.value = item
  quizDialog.value = true
  quizzesLoading.value = true
  quizForm.value = { course: null, cquiz: null }
  courseQuizOptions.value = []
  try {
    const [quizRes, courseRes] = await Promise.all([
      jobOfferQuizService.getByJobOffer(item.id),
      axios.get("/api/courses?pagination=false"),
    ])
    assignedQuizzes.value = quizRes
    courseOptions.value = (courseRes.data["hydra:member"] ?? []).map((c) => ({
      label: c.title,
      value: c["@id"],
    }))
  } catch (e) {
    showErrorNotification(e)
  } finally {
    quizzesLoading.value = false
  }
}

async function loadCourseQuizzes() {
  if (!quizForm.value.course) {
    courseQuizOptions.value = []
    return
  }
  try {
    const res = await axios.get(`/api/c_quizes?pagination=false&resourceNode.parent=${quizForm.value.course}`)
    courseQuizOptions.value = (res.data["hydra:member"] ?? []).map((q) => ({
      label: q.title,
      value: q["@id"],
    }))
  } catch (e) {
    showErrorNotification(e)
  }
}

async function addQuiz() {
  try {
    await jobOfferQuizService.create({
      jobOffer: activeOffer.value["@id"],
      course: quizForm.value.course,
      cquiz: quizForm.value.cquiz,
    })
    quizForm.value = { course: null, cquiz: null }
    courseQuizOptions.value = []
    assignedQuizzes.value = await jobOfferQuizService.getByJobOffer(activeOffer.value.id)
    showSuccessNotification(t("Saved"))
  } catch (e) {
    showErrorNotification(e)
  }
}

async function removeQuiz(item) {
  try {
    await jobOfferQuizService.remove(item["@id"])
    assignedQuizzes.value = await jobOfferQuizService.getByJobOffer(activeOffer.value.id)
    showSuccessNotification(t("Deleted"))
  } catch (e) {
    showErrorNotification(e)
  }
}

onMounted(async () => {
  await Promise.all([load(), loadSelects()])
})
</script>
