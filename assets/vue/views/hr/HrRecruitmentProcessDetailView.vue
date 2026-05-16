<template>
  <div class="space-y-6">
    <SectionHeader :title="process ? `${process.jobOfferTitle} — ${process.applicantName}` : t('Process')">
      <BaseButton
        :label="t('Back')"
        :route="{ name: 'HrRecruitmentProcesses' }"
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

    <template v-else-if="process">
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div class="bg-gray-50 rounded p-3">
          <span class="font-medium block text-gray-700">{{ t("Job offer") }}</span>
          <span>{{ process.jobOfferTitle }}</span>
        </div>
        <div class="bg-gray-50 rounded p-3">
          <span class="font-medium block text-gray-700">{{ t("Candidate") }}</span>
          <span>{{ process.applicantName }}</span>
        </div>
        <div class="bg-gray-50 rounded p-3">
          <span class="font-medium block text-gray-700">{{ t("Created by") }}</span>
          <span>{{ process.createdByName }}</span>
        </div>
        <div class="bg-gray-50 rounded p-3">
          <span class="font-medium block text-gray-700">{{ t("Date") }}</span>
          <span>{{ process.createdAt.slice(0, 10) }}</span>
        </div>
        <div
          v-if="process.notes"
          class="bg-gray-50 rounded p-3 col-span-2"
        >
          <span class="font-medium block text-gray-700">{{ t("Notes") }}</span>
          <span>{{ process.notes }}</span>
        </div>
      </div>

      <!-- Tracking timeline -->
      <div>
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-gray-700">
            {{ t("Process tracking") }}
          </h3>
          <BaseButton
            :label="t('Add tracking entry')"
            icon="plus"
            size="small"
            type="success"
            @click="openTrackingForm()"
          />
        </div>

        <div
          v-if="tracking.length === 0"
          class="text-center py-8 text-gray-400 text-sm"
        >
          {{ t("No tracking entries yet.") }}
        </div>

        <div
          v-else
          class="space-y-3"
        >
          <div
            v-for="entry in tracking"
            :key="entry.id"
            class="border rounded p-4 bg-white flex items-start justify-between gap-4"
          >
            <div class="flex-1 space-y-1">
              <span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                {{ entry.stageTitle }}
              </span>
              <div class="text-xs text-gray-400">
                {{ entry.createdAt.slice(0, 16).replace("T", " ") }}
                <span v-if="entry.supervisorName">— {{ entry.supervisorName }}</span>
              </div>
              <p
                v-if="entry.notes"
                class="text-sm text-gray-700"
              >
                {{ entry.notes }}
              </p>
            </div>
            <BaseButton
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDeleteTracking(entry)"
            />
          </div>
        </div>
      </div>
    </template>

    <!-- Add tracking entry dialog -->
    <BaseDialog
      v-model:is-visible="trackingDialog"
      :style="{ width: '480px' }"
      :title="t('Add tracking entry')"
    >
      <div class="space-y-4 pt-2">
        <BaseSelect
          id="track-stage"
          v-model="trackingForm.stage"
          :label="t('Stage')"
          :options="stageOptions"
          name="stage"
        />
        <BaseAutocomplete
          id="track-supervisor"
          v-model="trackingForm.supervisorObj"
          :label="t('Supervisor')"
          :search="searchUsers"
          option-label="fullName"
        />
        <BaseTextArea
          id="track-notes"
          v-model="trackingForm.notes"
          label="Notes"
          name="notes"
          rows="3"
        />
      </div>
      <template #footer>
        <BaseButton
          :disabled="!trackingForm.stage"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveTracking"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import * as processService from "../../services/hr/recruitmentProcessService"
import * as trackingService from "../../services/hr/recruitmentProcessTrackingService"
import * as stageService from "../../services/hr/recruitmentStageService"
import baseService from "../../services/baseService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()
const route = useRoute()

const loading = ref(true)
const process = ref(null)
const tracking = ref([])
const stageOptions = ref([])
const trackingDialog = ref(false)
const trackingForm = ref({ stage: null, supervisorObj: null, notes: "" })

async function load() {
  loading.value = true
  try {
    const [proc, entries] = await Promise.all([
      processService.getOne(`/api/recruitment_processes/${route.params.id}`),
      trackingService.getByProcess(route.params.id),
    ])

    process.value = proc
    tracking.value = entries
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function openTrackingForm() {
  trackingForm.value = { stage: null, supervisorObj: null, notes: "" }

  try {
    const stages = await stageService.getAll()

    stageOptions.value = stages.map((s) => ({ label: s.title, value: s["@id"] }))
  } catch (e) {
    console.error(e)
  }

  trackingDialog.value = true
}

async function searchUsers(query) {
  const result = await baseService.getCollection("/api/users", { search: query, itemsPerPage: 10 })

  return result.items
}

async function saveTracking() {
  try {
    await trackingService.create({
      process: `/api/recruitment_processes/${route.params.id}`,
      stage: trackingForm.value.stage,
      supervisor: trackingForm.value.supervisorObj?.["@id"] ?? null,
      notes: trackingForm.value.notes || null,
    })

    trackingDialog.value = false
    showSuccessNotification(t("Saved"))

    tracking.value = await trackingService.getByProcess(route.params.id)
  } catch (e) {
    showErrorNotification(e)
  }
}

function confirmDeleteTracking(entry) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await trackingService.remove(entry["@id"])

        showSuccessNotification(t("Deleted"))

        tracking.value = await trackingService.getByProcess(route.params.id)
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

onMounted(load)
</script>
