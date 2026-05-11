import baseService from "../baseService"

export async function getAll(params = {}) {
  const result = await baseService.getCollection("/api/recruitment_processes", { pagination: false, ...params })

  return result.items
}

export async function getByJobOffer(jobOfferId) {
  return getAll({ jobOffer: `/api/job_offers/${jobOfferId}` })
}

export async function getOne(iri) {
  return baseService.get(iri)
}

export async function create(payload) {
  return baseService.post("/api/recruitment_processes", payload)
}

export async function update(iri, payload) {
  return baseService.put(iri, payload)
}

export async function remove(iri) {
  return baseService.delete(iri)
}
