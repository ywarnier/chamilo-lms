import baseService from "../baseService"

export async function getAll(params = {}) {
  const { items } = await baseService.getCollection("/api/benefit_assignments", params)

  return items
}

export async function getMyBenefits(params = {}) {
  const { items } = await baseService.getCollection("/api/me/benefit_assignments", params)

  return items
}

export async function create(payload) {
  await baseService.post("/api/benefit_assignments", payload)
}

export async function remove(iri) {
  await baseService.delete(iri)
}

export async function notify(assignmentId, subject, message) {
  await baseService.post("/hr/benefit-notify", { assignmentId, subject, message }, true)
}
