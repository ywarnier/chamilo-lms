import baseService from "../baseService"

export async function getAll(params = {}) {
  const { items } = await baseService.getCollection("/api/diversity_criterias", params)

  return items
}

export async function create(payload) {
  await baseService.post("/api/diversity_criterias", payload)
}

export async function update(iri, payload) {
  await baseService.put(iri, payload)
}

export async function remove(iri) {
  await baseService.delete(iri)
}
