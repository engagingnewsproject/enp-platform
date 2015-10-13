function enp_splitTest(quiz1, quiz2) {
    var quizzes = [quiz1, quiz2]; // create an array of the objects
    var rand = Math.floor(Math.random() * 2); // returns 0 or 1
    document.write('<iframe frameBorder="0" height="'+quizzes[rand].height+'" width="'+quizzes[rand].width+'" src="http://dev/enp/iframe-quiz/?guid='+quizzes[rand].guid+'"></iframe>');
}


//Tests to make sure our randomizer is fairly accurate
/*
var i = 0;
var randOne = 0;
var randZero = 0;
var randWhat = 0;
while(i < 10000) {
    rand = Math.floor(Math.random() * 2);
    if(rand === 1) {
        randOne++;
    } else if (rand === 0) {
        randZero++;
    } else {
        randWhat++;
    }
    i++;
}
console.log('Rand One = '+randOne);
console.log('Rand Zero = '+randZero);
console.log('Rand What = '+randWhat);

/*
Rand One = 5016
Rand Two = 4984

Rand One = 5024
Rand Two = 4976

Rand One = 5063
Rand Two = 4937

Rand One = 5004
Rand Two = 4996

Rand One = 4925
Rand Two = 5075

Rand One = 4991
Rand Two = 5009
*/

/*
<script type="text/javascript" src="http://dev/enp/wp-content/themes/yaaburnee-themes/self-service-quiz/js/split-test.js"></script>
<script type="text/javascript">
<!--
enp_splitTest(
            {guid:"561bd8d78e7d65.41975989_a214b2272c96cb0000611041c2e7bd79",
            height:"280px",
            width:"336px"},
            {guid:"561bd968771419.60989973_21f2514fc304737a8950dafdc1afcd5a",
            height:"300px",
            width:"400px"}
);
//--></script>

var quizA = {guid:"561bd8d78e7d65.41975989_a214b2272c96cb0000611041c2e7bd79",
            height:"280px",
            width:"336px"};
var quizB = {guid:"561bd968771419.60989973_21f2514fc304737a8950dafdc1afcd5a",
            height:"300px",
            width:"400px"};

enp_splitTest(quizA, quizB);

*/
