//this function is used to retrieve the selected text from the text editor
function getSel() {
	var txtarea = document.getElementById( "content" );
	var start   = txtarea.selectionStart;
	var finish  = txtarea.selectionEnd;
	return txtarea.value.substring( start, finish );
}

QTags.addButton(
	"t_shortcode",
	"Insert Text",
	insertText
);

function insertText() {
	QTags.insertContent( '[text name="Text Title"]' );
}
QTags.addButton(
	"ta_shortcode",
	"Insert Textarea",
	insertTextarea
);

function insertTextarea() {
	QTags.insertContent( '[textarea name="Text Title"]' );
}
QTags.addButton(
	"s_shortcode",
	"Insert Select Dropdown",
	insertSelectb
);

function insertSelectb() {
	QTags.insertContent( '[select name="Text Title" options="option 1,option 2,option 3"]' );
}
QTags.addButton(
	"r_shortcode",
	"Insert Radio Options",
	insertRadiob
);

function insertRadiob() {
	QTags.insertContent( '[radio name="Text Title" options="option 1,option 2,option 3"]' );
}
QTags.addButton(
	"cb_shortcode",
	"Insert Checkbox Options",
	insertCheckboxb
);

function insertCheckboxb() {
	QTags.insertContent( '[checkbox name="Text Title" options="option 1,option 2,option 3"]' );
}
QTags.addButton(
	"dp_shortcode",
	"Insert Datepicker",
	insertDatepickerb
);

function insertDatepickerb() {
	QTags.insertContent( '[datepicker name="Datepicker Title"]' );
}
QTags.addButton(
	"i_shortcode",
	"Insert File Upload",
	insertInput
);

function insertInput() {
	QTags.insertContent( '[input name="File Name"]' );
}
QTags.addButton(
	"ngs_shortcode",
	"Insert Nigerian States",
	insertSelectStates
);

function insertSelectStates() {
	QTags.insertContent(
		'[select name="State" options="Abia,Adamawa,Akwa Ibom,Anambra,Bauchi,Bayelsa,Benue,Borno,Cross River,Delta,Ebonyi,Edo,Ekiti,Enugu,FCT,Gombe,Imo,Jigawa,Kaduna,Kano,Katsina,Kebbi,Kogi,Kwara,Lagos,Nasarawa,Niger,Ogun,Ondo,Osun,Oyo,Plateau,Rivers,Sokoto,Taraba,Yobe,Zamfara"]'
	);
}
QTags.addButton(
	"ctys_shortcode",
	"Insert All Countries",
	insertSelectCountries
);

function insertSelectCountries() {
	QTags.insertContent(
		'[select  name="country" options="Afghanistan, Albania, Algeria, American Samoa, Andorra, Angola, Anguilla, Antarctica, Antigua and Barbuda, Argentina, Armenia, Aruba, Australia, Austria, Azerbaijan, Bahamas, Bahrain, Bangladesh, Barbados, Belarus, Belgium, Belize, Benin, Bermuda, Bhutan, Bolivia, Bosnia and Herzegovina, Botswana, Bouvet Island, Brazil, British Indian Ocean Territory, Brunei Darussalam, Bulgaria, Burkina Faso, Burundi, Cambodia, Cameroon, Canada, Cape Verde, Cayman Islands, Central African Republic, Chad, Chile, China, Christmas Island, Cocos (Keeling) Islands, Colombia, Comoros, Congo, Congo, The Democratic Republic of The, Cook Islands, Costa Rica, Cote D’ivoire, Croatia, Cuba, Cyprus, Czech Republic, Denmark, Djibouti, Dominica, Dominican Republic, Ecuador, Egypt, El Salvador, Equatorial Guinea, Eritrea, Estonia, Ethiopia, Falkland Islands (Malvinas), Faroe Islands, Fiji, Finland, France, French Guiana, French Polynesia, French Southern Territories, Gabon, Gambia, Georgia, Germany, Ghana, Gibraltar, Greece, Greenland, Grenada, Guadeloupe, Guam, Guatemala, Guinea, Guinea-bissau, Guyana, Haiti, Heard Island and Mcdonald Islands, Holy See (Vatican City State), Honduras, Hong Kong, Hungary, Iceland, India, Indonesia, Iran, Islamic Republic of, Iraq, Ireland, Israel, Italy, Jamaica, Japan, Jordan, Kazakhstan, Kenya, Kiribati, Korea, Democratic People’s Republic of, Korea, Republic of, Kuwait, Kyrgyzstan, Lao People’s Democratic Republic, Latvia, Lebanon, Lesotho, Liberia, Libyan Arab Jamahiriya, Liechtenstein, Lithuania, Luxembourg, Macao, Macedonia, The Former Yugoslav Republic of, Madagascar, Malawi, Malaysia, Maldives, Mali, Malta, Marshall Islands, Martinique, Mauritania, Mauritius, Mayotte, Mexico, Micronesia, Federated States of, Moldova, Republic of, Monaco, Mongolia, Montserrat, Morocco, Mozambique, Myanmar, Namibia, Nauru, Nepal, Netherlands, Netherlands Antilles, New Caledonia, New Zealand, Nicaragua, Niger, Nigeria, Niue, Norfolk Island, Northern Mariana Islands, Norway, Oman, Pakistan, Palau, Palestinian Territory, Occupied, Panama, Papua New Guinea, Paraguay, Peru, Philippines, Pitcairn, Poland, Portugal, Puerto Rico, Qatar, Reunion, Romania, Russian Federation, Rwanda, Saint Helena, Saint Kitts and Nevis, Saint Lucia, Saint Pierre and Miquelon, Saint Vincent and The Grenadines, Samoa, San Marino, Sao Tome and Principe, Saudi Arabia, Senegal, Serbia and Montenegro, Seychelles, Sierra Leone, Singapore, Slovakia, Slovenia, Solomon Islands, Somalia, South Africa, South Georgia and The South Sandwich Islands, Spain, Sri Lanka, Sudan, Suriname, Svalbard and Jan Mayen, Swaziland, Sweden, Switzerland, Syrian Arab Republic, Taiwan, Province of China, Tajikistan, Tanzania, United Republic of, Thailand, Timor-leste, Togo, Tokelau, Tonga, Trinidad and Tobago, Tunisia, Turkey, Turkmenistan, Turks and Caicos Islands, Tuvalu, Uganda, Ukraine, United Arab Emirates, United Kingdom, United States, United States Minor Outlying Islands, Uruguay, Uzbekistan, Vanuatu, Venezuela, Viet Nam, Virgin Islands, British, Virgin Islands, U.S., Wallis and Futuna, Western Sahara, Yemen, Zambia, Zimbabwe"] '
	);
}