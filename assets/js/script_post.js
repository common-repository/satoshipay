var fromLumens = function (lumens, rate) {
  return (lumens * rate).toFixed(6).replace(/\.?0+$/, "");
};

var Rates = function(currency) {
  this.currency = currency.toLowerCase();
  this.rate = jQuery.Deferred();
  this.fetch();
};

/**
 * Fetch exchange rate for a currency from external API (async).
 */
Rates.prototype.fetch = function() {
  var self = this;
  jQuery.get('https://api-dev.satoshipay.io/staging/testnet/coinmarketcap/v1/cryptocurrency/quotes/latest?convert=EUR&symbol=XLM', function(res) {
    self.rate.resolve(res.data.XLM.quote.EUR.price);
  });
};

/**
 * Converts lumen amounts to fiat.
 * @param {number} lumens Amount as integer
 * @return {number}
 */
Rates.prototype.fromLumens = function(lumens) {
  var Promise = jQuery.Deferred();
  this.rate.done(function (rate) {
    Promise.resolve(fromLumens(lumens, rate));
  });
  return Promise.promise();
};

var convertEur = new Rates('eur');

jQuery(document).ready(function() {
  var lumens = jQuery('#satoshipay_pricing_satoshi').val() || 1;
  var updatePricingFiat = function(event) {
    var lumens = event.target.value;
    var max_limit = parseInt(jQuery('#satoshipay_pricing_satoshi').attr('max'));

    if (lumens > max_limit) {
      event.target.value = satoshis = max_limit;
    }

    convertEur.fromLumens(lumens).done(function (eur) {
      jQuery('#satoshipay_pricing_satoshi_fiat').html(lumens + ' lumens &cong; ' + eur + '&euro;');
    });
  };
  convertEur.fromLumens(lumens).done(function (eur) {
    jQuery('#satoshipay_pricing_satoshi_fiat').html(lumens + ' lumens &cong; ' + eur + '&euro;');
  });
  jQuery('#satoshipay_pricing_satoshi').on('change input', function(event){
    updatePricingFiat(event);
  });
});
