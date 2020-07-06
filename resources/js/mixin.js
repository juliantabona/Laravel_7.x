// Custom defined mixin object
var mixin = {
    data(){
        return {
            
        }
    },
    methods: {
        //  Methods here
        getValidVariableNameValidator(){

            //  Custom validation to detect if the variable name is valid
            const validVariableNameValidator = (rule, value, callback) => {
                
                //  This pattern to detect white spaces
                var has_white_spaces = /\s/; 

                //  This pattern will detect if the value starts with a character that is not a letter or underscore
                var valid_first_character = /^[^a-zA-Z_]/;

                /** This pattern matches any non-word character. Same as [^a-zA-Z_0-9].
                 *  Note that a word is definned as a to z, A to Z, 0 to 9, and the 
                 *  underscore "_"
                 */
                var valid_remaining_characters = /\W/g;

                //  Check for unauthourized spaces
                if ( has_white_spaces.test(value) == true ) {
                    
                    callback(new Error('This name must not have spaces. Use underscores "_" instead e.g "first_name", "_username", "age_less_than_30"'));
                
                //  Check if first character is invalid
                }else if ( valid_first_character.test(value) == true ) {
                    
                    callback(new Error('This name must start with a letter or underscore "_" e.g "first_name", "_username", "age_less_than_30"'));
                
                    //  Check if remaining characters are invalid
                }else if ( valid_remaining_characters.test(value) == true ) {
            
                    callback(new Error('This name must only contain letters, numbers and underscores "_" e.g "first_name", "_username", "age_less_than_30"'));

                } else {
                    callback();
                }
            };

            return validVariableNameValidator;
        }
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