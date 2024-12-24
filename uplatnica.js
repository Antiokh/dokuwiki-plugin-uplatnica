
function toLat(text) {
    const cyrillicToLatinMap = {
        'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'ђ': 'đ', 'е': 'e', 'ж': 'ž', 'з': 'z', 'и': 'i',
        'ј': 'j', 'к': 'k', 'л': 'l', 'љ': 'lj', 'м': 'm', 'н': 'n', 'њ': 'nj', 'о': 'o', 'п': 'p', 'р': 'r',
        'с': 's', 'т': 't', 'ћ': 'ć', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'č', 'џ': 'dž', 'ш': 'š',
        'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D', 'Ђ': 'Đ', 'Е': 'E', 'Ж': 'Ž', 'З': 'Z', 'И': 'I',
        'Ј': 'J', 'К': 'K', 'Л': 'L', 'Љ': 'Lj', 'М': 'M', 'Н': 'N', 'Њ': 'Nj', 'О': 'O', 'П': 'P', 'Р': 'R',
        'С': 'S', 'Т': 'T', 'Ћ': 'Ć', 'У': 'U', 'Ф': 'F', 'Х': 'H', 'Ц': 'C', 'Ч': 'Č', 'Џ': 'Dž', 'Ш': 'Š'
    };

    let latinText = '';
    for (let i = 0; i < text.length; i++) {
        const char = text[i];
        latinText += cyrillicToLatinMap[char] || char;
    }
    return latinText;
}

function cleanSum(currencyString) {
    // Step 1: Leave only digits, commas, and dots
    const step1 = currencyString.replace(/[^\d,.]/g, "");

    // Step 2: Replace all commas with dots
    const step2 = step1.replace(/,/g, '.');

    // Step 3: Find the index of the last dot
    const lastDotIndex = step2.lastIndexOf('.');

    // Step 4: Replace the last dot with a comma and remove other dots
    const step3 = step2.replace(/\./g, (match, index) => index === lastDotIndex ? ',' : '');

    // Step 5: If comma is at the first position, add '0' before
    const step4 = step3.startsWith(',') ? '0' + step3 : step3;

    // Step 6: If there is no comma, add ',00'
    const step5 = step4.includes(',') ? step4 : step4 + ',00';

    // Return the formatted currency string
    return step5;
}

function formatAccount(number) {
    let prefix = String(number).slice(0, 3);
    let rest = String(number).slice(3).padStart(15, '0');
    return prefix + rest;
}

function gen_qr(reciept_id){
  // https://ips.nbs.rs/PDF/pdfPreporukeValidacijaLat.pdf
  console.log(reciept_id);
  var payer =  toLat(jQuery('#'+reciept_id+' .field-payer p').text());
  var title =  toLat(jQuery('#'+reciept_id+' .field-title p').text());
  var recipient =  toLat(jQuery('#'+reciept_id+' .field-recipient p').text());
  var currency =  'RSD';
  var code =  jQuery('#'+reciept_id+' .field-code p').text().replace(/\D/g, "");
  var sum =  cleanSum(jQuery('#'+reciept_id+' .field-sum p').text());
  var account =  formatAccount(jQuery('#'+reciept_id+' .field-account p').text().replace(/\D/g, ""));
  var model = jQuery('#'+reciept_id+' .field-model p').text().replace(/\D/g, "").slice(-2).padStart(2, '0');
  var target =  jQuery('#'+reciept_id+' .field-target p').text().replace(/\D/g, "");
  var data = `K:PR|V:01|C:1|R:${account}|N:${recipient}|I:${currency}${sum}|P:${payer}|SF:${code}|S:${title}|RO:${model}${target}`;
 // console.log(data);
 qrcode.stringToBytes = qrcode.stringToBytesFuncs['UTF-8'];
  var qr = qrcode(0, 'M');
  qr.addData(data);
  qr.make();
  //qr.createDataURL();
  jQuery('#'+reciept_id+' div.qr-code-image').html(qr.createSvgTag(10,1,'Alt'));
  //$('.reciept div.qr-code-image svg').width('100%');
}

function update_qr(field_id) {
  var receipt_id = field_id.replace('payer_','');
  gen_qr(receipt_id);
}
