<template>
  <HrSurveyDistributionList
    category="training_need"
    :title="t('Training needs assessment')"
  />
</template>

<script setup>
import { useI18n } from "vue-i18n"
import HrSurveyDistributionList from "./HrSurveyDistributionList.vue"

const { t } = useI18n()
</script>
