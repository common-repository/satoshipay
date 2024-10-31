// Convert hex to data:image svg
export default (hex = '%23F3F3F4') => `data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='100%' height='100%'><rect width='100%' height='100%' fill='${hex}'/></svg>`
