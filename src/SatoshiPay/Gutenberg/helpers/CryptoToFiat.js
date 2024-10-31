import { cryptoToFiat } from '../../Utils'
const { withState } = wp.compose
import './CryptoToFiat.scss'
import { SvgIcon } from '../helpers'

const CryptoToFiat =  ({ crypto = 'XLM', fiat = 'EUR', value, cache, fiatValue, isLoading, setState }) => {
    const updateFiatValue = () => {
        setState({
            isLoading: true,
            cache: {
                crypto,
                fiat,
                value,
            }
        })
        cryptoToFiat({ crypto, fiat, value })
        .then(fiatValue => setState({ fiatValue, isLoading: false }))
    }

    const fiatSymbols = {
        'EUR': '€',
        'USD': '$',
        'GBP': '£',
    }

    if (
        (
            !fiatValue
            || ( crypto && crypto !== cache.crypto )
            || ( fiat && fiat !== cache.fiat )
            || ( value && value !== cache.value )
        )
        && value
        && !isLoading
    ){
        updateFiatValue()
    }

    return (
        <div className="crypto-to-fiat">
            <div className="crypto-to-fiat__content">
                appx. { fiatSymbols[fiat] } { (!isLoading && fiatValue) || '0.000' }
            </div>
            <SvgIcon
                type="reload"
                size="12"
                className={`crypto-to-fiat__loader ${isLoading ? 'loading' : ''}`}
                onClick={updateFiatValue}
            />
        </div>
    )
}

export default withState({
    cache: {
        crypto: 'XLM',
        fiat: 'EUR',
    },
    fiatValue: null,
    isLoading: false,
})(CryptoToFiat)
