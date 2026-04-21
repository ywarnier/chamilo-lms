<template>
  <div class="hr-my-benefits">
    <SectionHeader :title="t('My benefits')" />

    <BaseTable
      :is-loading="loading"
      :values="assignments"
    >
      <Column :header="t('Benefit')">
        <template #body="{ data }">
          <div>
            <p class="font-medium text-gray-800">{{ data.compensation.title }}</p>
            <p
              v-if="data.compensation.description"
              class="text-sm text-gray-500 mt-0.5"
              v-html="data.compensation.description"
            ></p>
          </div>
        </template>
      </Column>
      <Column :header="t('Comment')">
        <template #body="{ data }">
          <span
            v-if="data.comment"
            v-html="data.comment"
          />
          <span
            v-else
            class="text-gray-400"
            v-text="'—'"
          />
        </template>
      </Column>
      <Column :header="t('Score')">
        <template #body="{ data }">
          {{ data.compensation.score }}
        </template>
      </Column>
      <Column :header="t('Assigned on')">
        <template #body="{ data }">
          {{ data.assignmentDatetime?.slice(0, 10) }}
        </template>
      </Column>
      <Column :header="t('Assigned until')">
        <template #body="{ data }">
          {{ data.assignmentEndDatetime?.slice(0, 10) || "—" }}
        </template>
      </Column>
      <Column :header="t('Assigned by')">
        <template #body="{ data }">
          {{ data.assignmentAuthor?.fullName }}
        </template>
      </Column>
      <Column :header="t('Notifications sent')">
        <template #body="{ data }">
          <ul
            v-if="data.notifications && data.notifications.length"
            class="list-none m-0 p-0 text-xs text-gray-500 space-y-0.5"
          >
            <li
              v-for="(notification, i) in data.notifications"
              :key="i"
            >
              {{ notification.sentOn?.slice(0, 19).replace("T", " ") }}
            </li>
          </ul>
          <span
            v-else
            class="text-gray-400"
            v-text="'—'"
          />
        </template>
      </Column>
    </BaseTable>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { getMyBenefits } from "../../services/hr/benefitAssignmentService"

const { t } = useI18n()

const assignments = ref([])
const loading = ref(true)

async function loadAssignments() {
  loading.value = true
  try {
    assignments.value = await getMyBenefits()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(loadAssignments)
</script>
