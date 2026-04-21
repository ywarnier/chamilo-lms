<template>
  <div class="p-4">
    <nav v-if="securityStore.isAdmin || securityStore.isHRM" class="text-sm text-gray-500 mb-3 flex items-center gap-1">
      <router-link to="/admin" class="hover:underline text-blue-600">{{ t("Administration") }}</router-link>
      <span>/</span>
      <router-link to="/hr" class="hover:underline text-blue-600">{{ t("Human Resources") }}</router-link>
    </nav>
    <h1 class="text-xl font-semibold mb-4">{{ t("Organizational chart") }}</h1>

    <div v-if="isLoading" class="text-gray-500">{{ t("Loading…") }}</div>
    <div v-else-if="error" class="text-red-500">{{ error }}</div>
    <div v-else>
      <div class="flex gap-2 mb-4 border-b border-gray-200">
        <button
          v-if="settings.unitPublic"
          :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px',
            activeTab === 'units'
              ? 'border-blue-600 text-blue-700'
              : 'border-transparent text-gray-500 hover:text-gray-700']"
          @click="activeTab = 'units'"
        >
          {{ t("Unit hierarchy") }}
        </button>
        <button
          v-if="settings.peoplePublic"
          :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px',
            activeTab === 'people'
              ? 'border-blue-600 text-blue-700'
              : 'border-transparent text-gray-500 hover:text-gray-700']"
          @click="activeTab = 'people'"
        >
          {{ t("All staff") }}
        </button>
      </div>

      <div v-if="activeTab === 'units' && settings.unitPublic" class="overflow-auto">
        <svg ref="svgUnits" width="100%" />
      </div>
      <div v-if="activeTab === 'people' && settings.peoplePublic" class="overflow-auto">
        <svg ref="svgPeople" width="100%" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from "vue"
import { useI18n } from "vue-i18n"
import * as d3 from "d3"
import axios from "axios"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const securityStore = useSecurityStore()

const isLoading = ref(false)
const error = ref(null)
const activeTab = ref("units")
const settings = ref({ unitPublic: false, peoplePublic: false })
const svgUnits = ref(null)
const svgPeople = ref(null)

let nodes = []

function buildHierarchy(flatNodes, bossOnly) {
  const map = {}
  for (const n of flatNodes) {
    map[n.id] = {
      ...n,
      label: bossOnly ? (n.bossName ? `${n.title}\n${n.bossName}` : n.title) : n.title,
      people: bossOnly ? [] : n.staff,
      children: [],
    }
  }
  const roots = []
  for (const n of flatNodes) {
    if (n.parentId && map[n.parentId]) {
      map[n.parentId].children.push(map[n.id])
    } else {
      roots.push(map[n.id])
    }
  }
  return roots.length === 1 ? roots[0] : { id: 0, label: "", children: roots }
}

function renderTree(svgEl, hierarchyData, bossOnly) {
  if (!svgEl) return
  d3.select(svgEl).selectAll("*").remove()

  const nodeWidth = 180
  const LINE_H = 13
  // For the people tab each node grows to fit its staff list.
  // titleH is the reserved space above the first staff name.
  const TITLE_H = bossOnly ? 64 : 26

  function nodeH(d) {
    if (bossOnly) return TITLE_H
    const n = d.data.people?.length ?? 0
    return TITLE_H + Math.max(n, 1) * LINE_H + 6
  }

  const root = d3.hierarchy(hierarchyData)
  root.each((d) => { d._h = nodeH(d) })

  const maxH = Math.max(...root.descendants().map((d) => d._h))
  const layout = d3.tree().nodeSize([nodeWidth + 24, maxH + 40])
  layout(root)

  const desc = root.descendants()
  const xMin = Math.min(...desc.map((d) => d.x)) - nodeWidth / 2 - 20
  const xMax = Math.max(...desc.map((d) => d.x)) + nodeWidth / 2 + 20
  const yMax = Math.max(...desc.map((d) => d.y + d._h)) + 20

  const svg = d3.select(svgEl)
    .attr("viewBox", `${xMin} 0 ${xMax - xMin} ${yMax}`)
    .attr("height", yMax)

  const g = svg.append("g")

  // Links: exit the bottom-centre of the source, enter the top-centre of the target.
  g.selectAll("path.link")
    .data(root.links())
    .join("path")
    .attr("fill", "none")
    .attr("stroke", "#94a3b8")
    .attr("stroke-width", 1.5)
    .attr("d", ({ source: s, target: t }) => {
      const x1 = s.x, y1 = s.y + s._h
      const x2 = t.x, y2 = t.y
      const mid = (y1 + y2) / 2
      return `M${x1},${y1} C${x1},${mid} ${x2},${mid} ${x2},${y2}`
    })

  const nodeG = g.selectAll("g.node")
    .data(desc)
    .join("g")
    .attr("transform", (d) => `translate(${d.x - nodeWidth / 2},${d.y})`)

  nodeG.append("rect")
    .attr("width", nodeWidth)
    .attr("height", (d) => d._h)
    .attr("rx", 6)
    .attr("fill", "#eff6ff")
    .attr("stroke", "#93c5fd")
    .attr("stroke-width", 1.5)

  // Unit title
  nodeG.append("text")
    .attr("x", nodeWidth / 2)
    .attr("y", 16)
    .attr("text-anchor", "middle")
    .attr("font-size", "11px")
    .attr("font-weight", "600")
    .attr("fill", "#1e3a5f")
    .text((d) => d.data.label?.split("\n")[0] ?? "")

  // Boss name (units tab only)
  if (bossOnly) {
    nodeG.append("text")
      .attr("x", nodeWidth / 2)
      .attr("y", 34)
      .attr("text-anchor", "middle")
      .attr("font-size", "10px")
      .attr("fill", "#475569")
      .text((d) => d.data.label?.split("\n")[1] ?? "")
  }

  // Staff list (people tab): all names, no truncation
  if (!bossOnly) {
    nodeG.each(function (d) {
      const people = d.data.people ?? []
      const el = d3.select(this)
      if (people.length === 0) {
        el.append("text")
          .attr("x", nodeWidth / 2)
          .attr("y", TITLE_H + LINE_H)
          .attr("text-anchor", "middle")
          .attr("font-size", "9px")
          .attr("fill", "#9ca3af")
          .text("—")
        return
      }
      people.forEach((person, i) => {
        el.append("text")
          .attr("x", nodeWidth / 2)
          .attr("y", TITLE_H + (i + 1) * LINE_H)
          .attr("text-anchor", "middle")
          .attr("font-size", "9px")
          .attr("fill", person.isBoss ? "#1d4ed8" : "#374151")
          .text((person.isBoss ? "★ " : "") + person.fullName)
      })
    })
  }
}

async function load() {
  isLoading.value = true
  error.value = null
  try {
    const res = await axios.get("/organizational-chart-data")
    nodes = res.data.nodes ?? []
    settings.value = res.data.settings ?? { unitPublic: false, peoplePublic: false }
    if (!activeTab.value || (activeTab.value === "units" && !settings.value.unitPublic)) {
      activeTab.value = settings.value.peoplePublic ? "people" : "units"
    }
  } catch (e) {
    error.value = e.response?.status === 403 ? t("This chart is not publicly available.") : t("Failed to load chart data.")
  } finally {
    isLoading.value = false
  }
  // Draw only after isLoading=false so the v-if has rendered the SVG elements.
  if (!error.value) {
    await nextTick()
    drawCurrentTab()
  }
}

function drawCurrentTab() {
  if (activeTab.value === "units" && svgUnits.value) {
    renderTree(svgUnits.value, buildHierarchy(nodes, true), true)
  } else if (activeTab.value === "people" && svgPeople.value) {
    renderTree(svgPeople.value, buildHierarchy(nodes, false), false)
  }
}

watch(activeTab, async () => {
  await nextTick()
  drawCurrentTab()
})

onMounted(load)
</script>
