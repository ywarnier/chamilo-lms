import baseService from "../baseService"

export async function getMine() {
  const result = await baseService.getCollection("/api/job_offer_applications/mine", { pagination: false })
  return result.items
}

export async function getByJobOffer(jobOfferId) {
  const result = await baseService.getCollection("/api/job_offer_applications", {
    pagination: false,
    "jobOffer.id": jobOfferId,
  })
  return result.items
}

export async function getOne(iri) {
  return baseService.get(iri)
}

export async function create(payload) {
  return baseService.post("/api/job_offer_applications", payload)
}

export async function update(iri, payload) {
  return baseService.put(iri, payload)
}

export async function remove(iri) {
  return baseService.delete(iri)
}
