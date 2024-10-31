export default async ({ crypto = 'XLM', fiat = 'EUR', value = 0, toFixed = 3 }) => {
    const response = await fetch(`https://api-dev.satoshipay.io/staging/testnet/coinmarketcap/v1/cryptocurrency/quotes/latest?convert=${fiat}&symbol=${crypto}`, {
        method: 'GET',
        'Access-Control-Allow-Origin':'*'
    })
    const jsonRes = await response.json()
    const rate = jsonRes.data[crypto].quote[fiat].price
    return (value * rate).toFixed(toFixed)
}
