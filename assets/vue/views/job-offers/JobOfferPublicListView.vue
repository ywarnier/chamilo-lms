<template>
  <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <SectionHeader :title="t('Job offers')">
      <BaseButton
        v-if="isAuthenticated"
        :label="t('My applications')"
        :route="{ name: 'JobOfferMyApplications' }"
        icon="clipboard-list"
        type="secondary"
      />
    </SectionHeader>

    <div
      v-if="loading"
      class="text-center py-16 text-gray-400"
    >
      {{ t("Loading") }}…
    </div>

    <div
      v-else-if="!offers.length"
      class="text-center py-16 text-gray-400"
    >
      {{ t("No job offers available at this time.") }}
    </div>

    <div
      v-else
      class="space-y-4"
    >
      <div
        v-for="offer in offers"
        :key="offer['@id']"
        class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">
              {{ offer.title }}
            </h2>
            <p class="text-sm text-gray-500 mt-1">
              {{ offer.functionInUnitTitle }}
            </p>
          </div>
          <span class="shrink-0 bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-medium">
            {{ t("Open") }}
          </span>
        </div>

        <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-600">
          <span v-if="offer.salary">
            <span class="font-medium">{{ t("Salary") }}:</span> {{ offer.salary }}
          </span>
          <span v-if="offer.vacancy">
            <span class="font-medium">{{ t("Vacancies") }}:</span> {{ offer.vacancy }}
          </span>
          <span v-if="offer.expectedStartDate">
            <span class="font-medium">{{ t("Expected start date") }}:</span>
            {{ offer.expectedStartDate.slice(0, 10) }}
          </span>
          <span v-if="offer.contractTypeTitle">
            <span class="font-medium">{{ t("Contract type") }}:</span> {{ offer.contractTypeTitle }}
          </span>
        </div>

        <div class="mt-4 flex justify-end">
          <BaseButton
            :label="t('See details')"
            :route="{ name: 'JobOfferDetail', params: { id: offer.id } }"
            icon="arrow-right"
            type="primary"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useSecurityStore } from "../../store/securityStore"
import * as jobOfferService from "../../services/hr/jobOfferService"

const { t } = useI18n()
const securityStore = useSecurityStore()
const isAuthenticated = computed(() => securityStore.isAuthenticated)

const offers = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    offers.value = await jobOfferService.getPublic()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})
</script>
