<template>

    <Row>
        <Col span="8" :offset="8">
            <Card class="auth-form mt-5 pt-2">
                
                <!-- Heading -->
                <Divider orientation="left" class="font-weight-bold">Sign Up</Divider>

                <!-- Error Message Alert -->
                <Alert v-if="serverErrorMessage && !isLoading" type="warning">{{ serverErrorMessage }}</Alert>

                <Form ref="registerForm" :model="registerForm" :rules="registerFormRules">
                    
                    <!-- Enter Name -->
                    <FormItem prop="name" :error="serverNameError">
                        <Input type="text" v-model="registerForm.name" placeholder="Name" :disabled="isLoading"
                                @keyup.enter.native="handleSubmit()">
                            <Icon type="ios-person-outline" slot="prepend"></Icon>
                        </Input>
                    </FormItem>
                    
                    <!-- Enter Email -->
                    <FormItem prop="email" :error="serverEmailError">
                        <Input type="email" v-model="registerForm.email" placeholder="Email" :disabled="isLoading"
                                @keyup.enter.native="handleSubmit()">
                            <Icon type="ios-mail-outline" slot="prepend"></Icon>
                        </Input>
                    </FormItem>
                    
                    <!-- Enter Password -->
                    <FormItem prop="password" :error="serverPasswordError">
                        <Input type="password" v-model="registerForm.password" placeholder="Password" :disabled="isLoading"
                                @keyup.enter.native="handleSubmit()">
                            <Icon type="ios-lock-outline" slot="prepend"></Icon>
                        </Input>
                    </FormItem>
                    
                    <!-- Confirm Password -->
                    <FormItem prop="password_confirmation">
                        <Input type="password" v-model="registerForm.password_confirmation" placeholder="Confirm Password" :disabled="isLoading"
                                @keyup.enter.native="handleSubmit()">
                            <Icon type="ios-lock-outline" slot="prepend"></Icon>
                        </Input>
                    </FormItem>

                    <!-- Login Link -->
                    <router-link to="login">Login?</router-link>
                    
                    <!-- Sign Up Button -->
                    <FormItem v-if="!isLoading">
                        <Button type="primary" class="float-right" :disabled="isLoading" @click="handleSubmit()">Sign Up</Button>
                    </FormItem>

                    <!-- If we are loading, Show Loader -->
                    <Loader v-show="isLoading" class="mt-2">Signing up...</Loader>

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
                if (value !== this.registerForm.password) {
                    callback(new Error('The passwords provided do not match!'));
                } else {
                    callback();
                }
            };

            return {
                isLoading: false,
                registerForm: {
                    name: '',
                    email: '',
                    password: '',
                    password_confirmation: ''
                },
                registerFormRules: {
                    name: [
                        { required: true, message: 'Please enter your name', trigger: 'blur' },
                        { min: 3, message: 'Your name is too short', trigger: 'change' },
                        { max: 20, message: 'Your name is too long', trigger: 'change' }
                    ],
                    email: [
                        { required: true, message: 'Please enter your email', trigger: 'blur' },
                        { type: 'email', message: 'Please enter a valid email', trigger: 'blur' }
                    ],
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
            serverNameError(){
                return (this.serverErrors || {}).name;
            },
            serverEmailError(){
                return (this.serverErrors || {}).email;
            },
            serverPasswordError(){
                return (this.serverErrors || {}).password;
            }
        },
        methods: {
            handleSubmit(){

                //  Reset the server errors
                this.resetErrors();

                //  Validate the register form
                this.$refs['registerForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {
                        
                        //  Attempt to register
                        this.attemptRegistration();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot sign up yet',
                            duration: 6
                        });
                    }
                })
            },
            attemptRegistration(){

                //  Hold constant reference to the current Vue instance
                const self = this;

                //  Start loader
                self.isLoading = true;

                //  Attempt to register using the auth register method found in the auth.js file
                auth.register(
                    //  Pass registration details
                    this.registerForm.name, this.registerForm.email, 
                    this.registerForm.password, this.registerForm.password_confirmation
                    ).then((data) => {

                        //  Stop loader
                        self.isLoading = false;

                        //  Reset the registration form
                        self.resetRegisterForm();

                        //  Registration success message
                        this.$Message.success({
                            content: 'Account created!',
                            duration: 6
                        });

                        //  Redirect the user to the projects page
                        this.$router.push({ name: 'show-projects' });
                        
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
                                    //  Get the error key e.g 'email', 'password'
                                    var prop = Object.keys(errors)[i];
                                    //  Get the error value e.g 'These credentials do not match our records.'
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
            resetRegisterForm(){
                this.resetErrors();
                this.$refs['registerForm'].resetFields();
            }
        }
    }
</script>