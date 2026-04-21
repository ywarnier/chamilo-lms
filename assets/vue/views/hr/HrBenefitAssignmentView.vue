<template>
  <div class="space-y-6">
    <SectionHeader :title="t('Assigned benefits')">
      <BaseButton
        :label="t('Assign benefit')"
        icon="plus"
        type="success"
        @click="openAssignForm()"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="loading"
      :values="filteredAssignments"
    >
      <template #header>
        <div class="flex justify-end gap-4">
          <div class="flex-1 min-w-48">
            <BaseAutocomplete
              id="filter-user-search"
              v-model="filterUser"
              :label="t('Beneficiary')"
              :search="searchUsers"
              option-label="fullName"
            />
          </div>

          <div class="flex-1 min-w-48">
            <BaseSelect
              id="filter-benefit"
              v-model="filterCompensationId"
              :label="t('Benefit')"
              :options="compensationFilterOptions"
              allow-clear
              name="benefit"
            />
          </div>

          <div class="w-44">
            <BaseCalendar
              id="filter-date"
              v-model="filterDate"
              :label="t('From date')"
            />
          </div>

          <BaseButton
            :label="t('Reset')"
            icon="clear-all"
            type="plain"
            @click="resetFilters"
          />
        </div>
      </template>

      <Column
        :header="t('Beneficiary')"
        sortable
      >
        <template #body="{ data }">
          {{ data.user.fullName }}
        </template>
      </Column>
      <Column :header="t('Benefit')">
        <template #body="{ data }">
          {{ data.compensation.title }}
        </template>
      </Column>
      <Column :header="t('Score')">
        <template #body="{ data }">
          {{ data.compensation.score }}
        </template>
      </Column>
      <Column :header="t('Economical equivalent')">
        <template #body="{ data }">
          {{ data.economicalEquivalent !== null ? data.economicalEquivalent : "—" }}
        </template>
      </Column>
      <Column :header="t('Assigned on')">
        <template #body="{ data }">
          {{ data.assignmentDatetime.slice(0, 10) }}
        </template>
      </Column>
      <Column :header="t('Assigned until')">
        <template #body="{ data }">
          {{ data.assignmentEndDatetime ? data.assignmentEndDatetime.slice(0, 10) : "—" }}
        </template>
      </Column>
      <Column :header="t('Assigned by')">
        <template #body="{ data }">
          {{ data.assignmentAuthor.fullName }}
        </template>
      </Column>
      <Column :exportable="false">
        <template #body="{ data }">
          <div class="flex justify-end gap-2">
            <BaseButton
              :label="t('Notify')"
              icon="bell-outline"
              only-icon
              size="small"
              type="primary-text"
              @click="openNotifyForm(data)"
            />
            <BaseButton
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDeleteAssignment(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <!-- Assign dialog -->
    <BaseDialog
      v-model:is-visible="assignDialog"
      :title="t('Assign benefit')"
      :style="{ width: '520px' }"
    >
      <div class="space-y-4 pt-2">
        <BaseSelect
          id="assign-benefit"
          v-model="assignForm.compensation"
          :label="t('Benefit')"
          :options="compensationOptions"
          :placeholder="t('Select a benefit')"
          name="benefit"
        />

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-sm font-medium text-gray-700">{{ t("Beneficiary") }}</label>
            <div class="flex rounded border border-gray-300 overflow-hidden text-xs">
              <button
                class="px-2 py-0.5 transition-colors"
                :class="beneficiaryMode === 'search' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                type="button"
                @click="setBeneficiaryMode('search')"
              >{{ t("Search") }}</button>
              <button
                class="px-2 py-0.5 transition-colors border-l border-gray-300"
                :class="beneficiaryMode === 'url' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                type="button"
                @click="setBeneficiaryMode('url')"
              >{{ t("URL") }}</button>
            </div>
          </div>
          <BaseAutocomplete
            v-if="beneficiaryMode === 'search'"
            id="assign-user"
            v-model="selectedUser"
            :search="searchUsers"
            option-label="fullName"
          />
          <input
            v-else
            v-model="beneficiaryUrl"
            name="beneficiary_url"
            type="url"
            :placeholder="t('Paste user URL or IRI…')"
            class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
          >
        </div>

        <BaseInputNumber
          id="assign-economical-equivalent"
          v-model="assignForm.economicalEquivalent"
          :label="t('Economical equivalent')"
          :min="0"
          :step="0.01"
          name="economical_equivalent"
        />

        <BaseCalendar
          id="assign-date-range"
          v-model="assignDateRange"
          :label="t('Assignment period')"
          type="range"
        />

        <BaseTextArea
          id="assign-comment"
          v-model="assignForm.comment"
          label="Comment"
          name="comment"
          rows="3"
        />
      </div>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="assignDialog = false"
        />
        <BaseButton
          :disabled="!assignForm.compensation || (beneficiaryMode === 'search' ? !selectedUser : !beneficiaryUrl.trim()) || !assignDateRange?.[0]"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveAssignment"
        />
      </template>
    </BaseDialog>

    <!-- Notify dialog -->
    <BaseDialog
      v-model:is-visible="notifyDialog"
      :title="t('Send notification')"
      :style="{ width: '520px' }"
    >
      <div class="space-y-4 pt-2">
        <BaseInputText
          id="notify-subject"
          v-model="notifyForm.subject"
          :label="t('Subject')"
          name="subject"
        />
        <BaseTextArea
          id="notify-message"
          v-model="notifyForm.message"
          label="Message"
          name="message"
          rows="6"
        />
      </div>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="notifyDialog = false"
        />
        <BaseButton
          :disabled="!notifyForm.subject || !notifyForm.message"
          :label="t('Send')"
          icon="send"
          type="success"
          @click="sendNotification"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import * as benefitAssignmentService from "../../services/hr/benefitAssignmentService"
import * as compensationService from "../../services/hr/compensationService"
import userService from "../../services/userService"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const assignments = ref([])
const compensations = ref([])
const loading = ref(true)

const filterUser = ref(null)
const filterCompensationId = ref(null)
const filterDate = ref(null)

const assignDialog = ref(false)
const notifyDialog = ref(false)
const notifyAssignment = ref(null)

const selectedUser = ref(null)
const beneficiaryMode = ref("search")
const beneficiaryUrl = ref("")
const assignDateRange = ref(null)

const assignForm = ref({
  compensation: null,
  economicalEquivalent: 0,
  comment: "",
})

const notifyForm = ref({ subject: "", message: "" })

const compensationOptions = computed(() => compensations.value.map((c) => ({ label: c.title, value: c["@id"] })))

const compensationFilterOptions = computed(() => compensations.value.map((c) => ({ label: c.title, value: c.id })))

const filteredAssignments = computed(() => {
  return assignments.value.filter((a) => {
    if (filterUser.value && a.user["@id"] !== filterUser.value["@id"]) {
      return false
    }

    if (filterCompensationId.value && a.compensation.id !== filterCompensationId.value) {
      return false
    }

    if (filterDate.value) {
      const filterStr =
        filterDate.value instanceof Date
          ? filterDate.value.toISOString().slice(0, 10)
          : String(filterDate.value).slice(0, 10)

      if (a.assignmentDatetime.slice(0, 10) < filterStr) {
        return false
      }
    }

    return true
  })
})

async function loadAssignments() {
  loading.value = true
  try {
    assignments.value = await benefitAssignmentService.getAll()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function loadCompensations() {
  try {
    compensations.value = await compensationService.getAll()
  } catch (e) {
    console.error(e)
  }
}

async function searchUsers(query) {
  const result = await userService.findBySearchTerm(query)

  return result.items
}

function resetFilters() {
  filterUser.value = null
  filterCompensationId.value = null
  filterDate.value = null
}

function setBeneficiaryMode(mode) {
  beneficiaryMode.value = mode
  selectedUser.value = null
  beneficiaryUrl.value = ""
}

function openAssignForm() {
  assignForm.value = {
    compensation: null,
    economicalEquivalent: 0,
    comment: "",
  }
  beneficiaryMode.value = "search"
  selectedUser.value = null
  beneficiaryUrl.value = ""
  assignDateRange.value = [new Date(), null]
  assignDialog.value = true
}

function openNotifyForm(assignment) {
  notifyAssignment.value = assignment

  notifyForm.value = {
    subject: t("Benefit notification") + ": " + assignment.compensation.title,
    message: t('Dear {0}, you have been assigned the benefit "{1}". Please log in to review it.', [
      assignment.user.fullName,
      assignment.compensation.title,
    ]),
  }

  notifyDialog.value = true
}

async function saveAssignment() {
  const userIri = beneficiaryMode.value === "url" ? beneficiaryUrl.value.trim() : (selectedUser.value?.["@id"] ?? null)

  const payload = {
    compensation: assignForm.value.compensation,
    user: userIri,
    economicalEquivalent: assignForm.value.economicalEquivalent,
    assignmentDatetime: assignDateRange.value?.[0] ? new Date(assignDateRange.value[0]).toISOString() : null,
    assignmentEndDatetime: assignDateRange.value?.[1] ? new Date(assignDateRange.value[1]).toISOString() : null,
    comment: assignForm.value.comment || null,
  }

  try {
    await benefitAssignmentService.create(payload)

    assignDialog.value = false
    showSuccessNotification(t("Saved"))

    await loadAssignments()
  } catch (e) {
    showErrorNotification(e)
  }
}

async function sendNotification() {
  if (!notifyAssignment.value) {
    return
  }

  try {
    await benefitAssignmentService.notify(notifyAssignment.value.id, notifyForm.value.subject, notifyForm.value.message)

    notifyDialog.value = false
    showSuccessNotification(t("Notification sent"))

    await loadAssignments()
  } catch (e) {
    showErrorNotification(e)
  }
}

function confirmDeleteAssignment(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await benefitAssignmentService.remove(item["@id"])

        showSuccessNotification(t("Deleted"))

        await loadAssignments()
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

onMounted(async () => {
  await Promise.all([loadAssignments(), loadCompensations()])
})
</script>
