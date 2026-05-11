<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">
      {{ t("Social Responsibility") }}
    </h1>
    <p class="text-gray-500 mb-8">
      {{ t("Our commitments to the UN Sustainable Development Goals") }}
    </p>

    <div
      v-if="isLoading"
      class="flex justify-center py-16"
    >
      <span class="text-gray-400">{{ t("Loading") }}…</span>
    </div>

    <div
      v-else-if="goals.length === 0"
      class="text-gray-500 text-center py-16"
    >
      {{ t("No published SDG commitments yet.") }}
    </div>

    <div
      v-else
      class="space-y-10"
    >
      <article
        v-for="goal in goals"
        :key="goal.id"
        class="flex gap-6 border-b border-gray-100 pb-10"
      >
        <img
          :src="`/img/sdg_icons/goal_${goal.sdgNumber}.png`"
          :alt="`SDG ${goal.sdgNumber}`"
          class="w-24 h-24 object-contain flex-shrink-0"
        >
        <div class="flex-1">
          <h2 class="text-xl font-semibold text-gray-800 mb-1">
            {{ goal.sdgNumber }}. {{ goal.title }}
          </h2>
          <p class="text-gray-700 mb-4 whitespace-pre-line">{{ goal.description }}</p>
          <div
            v-if="goal.enforcement"
            class="prose prose-sm max-w-none text-gray-600"
            v-html="goal.enforcement"
          />
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import * as goalService from "../../services/hr/socialResponsibilityGoalService"

const { t } = useI18n()

const goals = ref([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const lang = document.documentElement.lang || "en"
    goals.value = await goalService.getPublic(lang)
  } catch {
    goals.value = []
  } finally {
    isLoading.value = false
  }
})
</script>
