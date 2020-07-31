// Custom defined mixin object
var mixin = {

    methods: {

        getUniqueNameValidator(){

            //  Custom validation to detect matching headers
            const uniqueNameValidator = (rule, value, callback) => {

                //  Check if headers with the same name exist
                var similarNamesExist = this.headers.filter( (header, index) => { 

                    //  Skip checking the current header name
                    if( this.index == index ){
                        return false;
                    }

                    //  If the given value matches the header
                    return (value == header.name);
                }).length;

                //  If headers with a similar name exist
                if (similarNamesExist) {
                    callback(new Error('This header is already in use'));
                } else {
                    callback();
                }
            };

            return uniqueNameValidator;

        },

        getHasValueValidator(){

            //  Custom validation to detect if header value has been provided
            const hasValueValidator = (rule, value, callback) => {

                //  If we are not using code editor mode and the value has not been provided
                if ( !this.headerForm.value.code_editor_mode && !this.headerForm.value.text) {
                    callback(new Error('Please enter your header value'));
                } else {
                    callback();
                }
            };

            return hasValueValidator;

        }

    }
}

export default mixin;