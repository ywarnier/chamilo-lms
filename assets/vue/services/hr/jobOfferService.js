import baseService from "../baseService"

export async function getAll(params = {}) {
  const result = await baseService.getCollection("/api/job_offers", { pagination: false, ...params })
  return result.items
}

export async function getPublic() {
  const result = await baseService.getCollection("/api/job_offers/public", { pagination: false })
  return result.items
}

export async function getOne(iri) {
  return baseService.get(iri)
}

export async function create(payload) {
  return baseService.post("/api/job_offers", payload)
}

export async function update(iri, payload) {
  return baseService.put(iri, payload)
}

export async function remove(iri) {
  return baseService.delete(iri)
}
