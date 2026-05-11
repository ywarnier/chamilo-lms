<template>
  <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <SectionHeader :title="t('My applications')">
      <BaseButton
        :label="t('View job offers')"
        :route="{ name: 'JobOfferPublicList' }"
        icon="arrow-left"
        type="plain"
      />
    </SectionHeader>

    <div
      v-if="loading"
      class="text-center py-16 text-gray-400"
    >
      {{ t("Loading") }}…
    </div>

    <div
      v-else-if="!applications.length"
      class="text-center py-16 text-gray-400"
    >
      {{ t("You have not submitted any applications yet.") }}
    </div>

    <div
      v-else
      class="space-y-4"
    >
      <div
        v-for="app in applications"
        :key="app['@id']"
        class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">
              {{ app.jobOfferTitle }}
            </h2>
            <p class="text-sm text-gray-500 mt-1">{{ t("Applied on") }}: {{ app.createdAt.slice(0, 10) }}</p>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <span
              v-if="app.totalScore !== null"
              class="text-sm font-medium text-gray-700"
            >
              {{ t("Score") }}: {{ app.totalScore }}%
            </span>
            <span
              :class="statusClass(app.hired)"
              class="px-2 py-0.5 rounded text-xs font-medium"
            >
              {{ statusLabel(app.hired) }}
            </span>
          </div>
        </div>

        <div
          v-if="app.observation"
          class="mt-4 bg-gray-50 rounded p-3 text-sm text-gray-700"
        >
          <span class="font-medium block mb-1">{{ t("HR feedback") }}</span>
          {{ app.observation }}
        </div>

        <div
          v-if="app.linkedQuizzes && app.linkedQuizzes.length"
          class="mt-4 flex flex-wrap gap-2"
        >
          <a
            v-for="quiz in app.linkedQuizzes"
            :key="quiz.exerciseUrl"
            :href="quiz.exerciseUrl"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded text-sm bg-blue-600 text-white hover:bg-blue-700 transition-colors"
            target="_blank"
          >
            <span class="mdi mdi-quiz text-base" />
            {{ quiz.title }}
          </a>
        </div>

        <div
          v-if="app.hired === STATUS_STAND_BY"
          class="mt-4 flex justify-end"
        >
          <BaseButton
            :label="t('Withdraw application')"
            icon="delete"
            type="danger-text"
            @click="confirmWithdraw(app)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import * as jobOfferApplicationService from "../../services/hr/jobOfferApplicationService"

const STATUS_STAND_BY = 0
const STATUS_HIRED = 1

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const applications = ref([])
const loading = ref(true)

function statusLabel(hired) {
  if (STATUS_HIRED === hired) return t("Hired")
  if (2 === hired) return t("Not selected")
  return t("Under review")
}

function statusClass(hired) {
  if (STATUS_HIRED === hired) return "bg-green-100 text-green-700"
  if (2 === hired) return "bg-gray-100 text-gray-700"
  return "bg-blue-100 text-blue-700"
}

function confirmWithdraw(app) {
  requireConfirmation({
    message: t("Are you sure you want to withdraw this application?"),
    accept: async () => {
      try {
        await jobOfferApplicationService.remove(app["@id"])
        applications.value = applications.value.filter((a) => a["@id"] !== app["@id"])
        showSuccessNotification(t("Application withdrawn."))
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

onMounted(async () => {
  try {
    applications.value = await jobOfferApplicationService.getMine()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})
</script>
