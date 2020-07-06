// Custom defined mixin object
var mixin = {

    methods: {

        getUniqueNameValidator(){

            //  Custom validation to detect matching query params
            const uniqueNameValidator = (rule, value, callback) => {

                //  Check if query params with the same name exist
                var similarNamesExist = this.queryParams.filter( (queryParam, index) => { 

                    //  Skip checking the current query param name
                    if( this.index == index ){
                        return false;
                    }

                    //  If the given value matches the query param
                    return (value == queryParam.name);
                }).length;

                //  If query params with a similar name exist
                if (similarNamesExist) {
                    callback(new Error('This query param is already in use'));
                } else {
                    callback();
                }
            };

            return uniqueNameValidator;

        },

        getHasValueValidator(){

            //  Custom validation to detect if query param value has been provided
            const hasValueValidator = (rule, value, callback) => {

                //  If we are not using code editor mode and the value has not been provided
                if ( !this.queryParamForm.value.code_editor_mode && !this.queryParamForm.value.text) {
                    callback(new Error('Please enter your query param value'));
                } else {
                    callback();
                }
            };

            return hasValueValidator;

        }

    }
}

export default mixin;