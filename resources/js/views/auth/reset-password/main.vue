<template>

    <Row>
        <Col span="8" :offset="8">
            <Card class="auth-form mt-5 pt-2">
                
                <!-- Heading -->
                <Divider orientation="left" class="font-weight-bold">Change Password</Divider>

                <p class="text-dark mb-3 pb-3 border-bottom-dashed">You are almost done, just enter and confirm your new password</p>

                <!-- Error Message Alert -->
                <Alert v-if="serverErrorMessage && !isLoading" type="warning">{{ serverErrorMessage }}</Alert>

                <Form ref="passwordResetForm" :model="passwordResetForm" :rules="passwordResetFormRules" @submit.native.prevent="handleSubmit()">
                                       
                    <!-- Enter Password -->
                    <FormItem prop="password" :error="serverPasswordError">
                        <Input type="password" v-model="passwordResetForm.password" placeholder="Password" :disabled="isLoading"
                                @keyup.enter.native="handleSubmit()">
                            <Icon type="ios-lock-outline" slot="prepend"></Icon>
                        </Input>
                    </FormItem>
                    
                    <!-- Confirm Password -->
                    <FormItem prop="password_confirmation">
                        <Input type="password" v-model="passwordResetForm.password_confirmation" placeholder="Confirm Password" :disabled="isLoading"
                                @keyup.enter.native="handleSubmit()">
                            <Icon type="ios-lock-outline" slot="prepend"></Icon>
                        </Input>
                    </FormItem>
                    
                    <!-- Change Password Button -->
                    <FormItem v-if="!isLoading">
                        <Button type="primary" icon="ios-refresh" class="float-right" :disabled="isLoading" @click="handleSubmit()">
                            Change Password
                        </Button>
                    </FormItem>

                    <!-- If we are loading, Show Loader -->
                    <Loader v-show="isLoading" class="mt-2">Sending email...</Loader>

                </Form>
            </Card>
        </Col>
    </Row>

</template>
<script>
    
    import Loader from './../../../components/_common/loaders/default.vue';

    export default {
        components: { Loader },
        data () {

            //  Custom validation to detect non matching passwords
            const samePasswordValidator = (rule, value, callback) => {
                //  If the confirm password does not match the password
                if (value !== this.passwordResetForm.password) {
                    callback(new Error('The passwords provided do not match!'));
                } else {
                    callback();
                }
            };

            return {
                isLoading: false,
                passwordResetForm: {
                    password: '',
                    password_confirmation: ''
                },
                passwordResetFormRules: {
                    password: [
                        { required: true, message: 'Please enter your password.', trigger: 'blur' },
                        { type: 'string', min: 6, message: 'The password length must be more than 6 characters', trigger: 'change' }
                    ],
                    password_confirmation: [
                        { required: true, message: 'Confirm your password.', trigger: 'blur' },
                        { validator: samePasswordValidator, trigger: 'change' }
                    ]
                },
                serverErrors: [],
                serverErrorMessage: ''
            }
        },
        computed: {
            serverPasswordError(){
                return (this.serverErrors || {}).password;
            }
        },
        methods: {
            handleSubmit(){

                //  Reset the server errors
                this.resetErrors();

                //  Validate the password reset form
                this.$refs['passwordResetForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {
                        
                        //  Attempt to reset password
                        this.attemptResetPassword();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, we cannot change your password yet',
                            duration: 6
                        });
                    }
                })
            },
            attemptResetPassword(){

                //  Hold constant reference to the current Vue instance
                const self = this;

                //  Start loader
                self.isLoading = true;
                
                //  Attempt to reset the password using the auth resetPassword method found in the auth.js file
                auth.resetPassword( 
                    this.$route.query.email, this.$route.query.token,
                    this.passwordResetForm.password, this.passwordResetForm.password_confirmation)
                    .then((data) => {

                        //  Stop loader
                        self.isLoading = false;

                        //  Reset the password reset form
                        self.resetForm();

                        //  Password reset success message
                        self.$Message.success({
                            content: 'You password was changed successfully!',
                            duration: 6
                        });

                        //  Redirect the user to the projects page
                        self.$router.push({ name: 'show-projects' });
                        
                    }).catch((response) => {
                
                        console.log(response);

                        //  Stop loader
                        self.isLoading = false;

                        //  Get the error response data
                        let data = (response || {}).data;
                            
                        //  Get the response errors
                        var errors = (data || {}).errors;

                        //  Set the general error message
                        self.serverErrorMessage = (data || {}).message;

                        /** 422: Validation failed. Incorrect credentials
                         */
                        if((response || {}).status === 422){

                            //  If we have errors
                            if(_.size(errors)){
                                
                                //  Set the server errors
                                self.serverErrors = errors;

                                //  Foreach error
                                for (var i = 0; i < _.size(errors); i++) {
                                    //  Get the error key e.g 'email'
                                    var prop = Object.keys(errors)[i];
                                    //  Get the error value e.g 'The account using the email provided does not exist'
                                    var value = Object.values(errors)[i][0];

                                    //  Dynamically update the serverErrors for View UI to display the error on the appropriate form item
                                    self.serverErrors[prop] = value;
                                }

                            }
                        }

                });
            },
            resetErrors(){
                this.serverErrorMessage = '';
                this.serverErrors = [];
            },
            resetForm(){
                this.resetErrors();
                this.$refs['passwordResetForm'].resetFields();
            }
        },
        created(){
            //  If the token or email hasn't been provided
            if( !this.$route.query.token || !this.$route.query.email ){
                
                //  Return back to the forgot password page
                this.$router.push({ name: 'forgot-password' });

            }
        }
    }
</script>