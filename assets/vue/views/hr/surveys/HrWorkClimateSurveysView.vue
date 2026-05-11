<template>
  <HrSurveyDistributionList
    category="work_climate"
    :title="t('Work climate surveys')"
  />
</template>

<script setup>
import { useI18n } from "vue-i18n"
import HrSurveyDistributionList from "./HrSurveyDistributionList.vue"

const { t } = useI18n()
</script>
