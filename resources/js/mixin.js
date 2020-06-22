// Custom defined mixin object
var mixin = {
    data(){
        return {
            mixin_data: 'Hello from mixin!'
        }
    },
    methods: {
        //  Methods here
    },
    computed: {
        //  Computed here
    },
    directives: {
        focus: {
            inserted: function (el, binding) {
                //  DOM is not updated yet
                VueInstance.$nextTick(function () {
                    
                    /** DOM is now updated
                     * 
                     *  If the element reference was provided
                     */ 
                    if( binding.value ){

                        //  Focus on the given reference nested within the current element
                        $(el).find(binding.value).focus();

                    }else{

                        //  Focus on the current element
                        el.focus();

                    }
                    
                })
            }
        }
    },
    filters: {
        firstLetter: function (word) {

            //  Get the first letter
            return word.charAt(0).toUpperCase();
        },
        firstLetterColor: function (word) {

            //  Get the first letter
            var letter = word.charAt(0).toUpperCase();

            var colors = [
                    
                    'f44336', 'e91e63', '9c27b0', '673ab7', '3f51b5', '2196f3', '03a9f4',
                    '009688', '4caf50', '8bc34a', 'cddc39', 'ffeb3b', 'ffc107', 'ff9800',
                    '00bcd4', 

                    'f44336', 'e91e63', '9c27b0', '673ab7', '3f51b5', '2196f3', '03a9f4',
                    '009688', '4caf50', '8bc34a', 'cddc39'
                ];  

            var letters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
            
            for (let index = 0; index < letters.length; index++) {
                if( letters[index] == letter ){
                    return '#'+colors[index];
                }
            }
        }
    }
  }

export default mixin;