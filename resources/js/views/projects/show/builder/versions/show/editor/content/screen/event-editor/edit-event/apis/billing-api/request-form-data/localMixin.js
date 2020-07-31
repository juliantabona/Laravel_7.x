// Custom defined mixin object
var mixin = {

    methods: {

        getUniqueNameValidator(){

            //  Custom validation to detect matching form data params
            const uniqueNameValidator = (rule, value, callback) => {

                //  Check if form data params with the same name exist
                var similarNamesExist = this.formDataParams.filter( (formDataParam, index) => { 

                    //  Skip checking the current form data param name
                    if( this.index == index ){
                        return false;
                    }

                    //  If the given value matches the form data param
                    return (value == formDataParam.name);
                }).length;

                //  If form data params with a similar name exist
                if (similarNamesExist) {
                    callback(new Error('This form data param is already in use'));
                } else {
                    callback();
                }
            };

            return uniqueNameValidator;

        },

        getHasValueValidator(){

            //  Custom validation to detect if form data param value has been provided
            const hasValueValidator = (rule, value, callback) => {

                //  If we are not using code editor mode and the value has not been provided
                if ( !this.formDataParamForm.value.code_editor_mode && !this.formDataParamForm.value.text) {
                    callback(new Error('Please enter your form data param value'));
                } else {
                    callback();
                }
            };

            return hasValueValidator;

        }

    }
}

export default mixin;