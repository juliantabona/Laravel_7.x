<template>
    <div>
        <!-- Modal 

             Note: drawerVisible and detectClose() are imported from the drawerMixin.
             They are used to allow for opening and closing the drawer properly
             during the v-if conditional statement of the parent component. It
             is important to note that <Modal> does not open/close well with
             v-if statements by default, therefore we need to add additional
             functionality to enhance the experience. Refer to drawerMixin.
        -->
        <Drawer
            width="650"
            :okText="okText" 
            cancelText="Cancel"
            :title="drawerTitle"
            :maskClosable="false"
            v-model="drawerVisible"
            @on-visible-change="detectClose">

            <Alert v-if="serverErrorMessage && !isCreating && !isSavingChanges" type="warning">{{ serverErrorMessage }}</Alert>

            <Form ref="userAccountForm" :model="userAccountForm" :rules="userAccountFormRules" @submit.native.prevent="handleSubmit()">

                <!-- Enter First Name -->
                <FormItem label="First Name" prop="first_name" class="mb-3">
                    <Poptip trigger="focus" placement="top" word-wrap class="poptip-w-100"
                            content="Provide the user's first name">
                        <Input type="text" v-model="userAccountForm.first_name" placeholder="Enter first name" 
                                :disabled="isLoadingAnything" class="w-100">
                        </Input>
                    </Poptip>
                </FormItem>

                <!-- Enter Last Name -->
                <FormItem label="Last Name" prop="last_name" class="mb-3">
                    <Poptip trigger="focus" placement="top" word-wrap class="poptip-w-100"
                            content="Provide the user's last name">
                        <Input type="text" v-model="userAccountForm.last_name" placeholder="Enter last name" 
                                :disabled="isLoadingAnything" class="w-100">
                        </Input>
                    </Poptip>
                </FormItem>

                <!-- Enter Mobile Number -->
                <FormItem label="Mobile Number" prop="mobile_number" class="mb-3">
                    <Poptip trigger="focus" placement="top" word-wrap class="poptip-w-100"
                            content="Provide the user's mobile number">
                        <Input type="text" v-model="userAccountForm.mobile_number" placeholder="Enter mobile number" 
                                :disabled="isLoadingAnything" class="w-100">
                        </Input>
                    </Poptip>
                </FormItem>

                <!-- Metadata -->
                <div v-if="userAccountForm.metadata">

                    <userAccountMetadataTable :metadata="userAccountForm.metadata"></userAccountMetadataTable>

                    <Divider orientation="left" class="font-weight-bold mt-4">Additional Fields</Divider>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(value, name) in userAccountForm.metadata" :key="name">
                                <td>{{ name }}</td>
                                <td>

                                    <template v-if="(typeof value == 'string') || (typeof value == 'undefined') || (value == null)">
                                    
                                        <!-- If the given value is a String -->
                                        <Input type="text" v-model="userAccountForm.metadata[name]"
                                                :disabled="isLoadingAnything" class="w-100">
                                        </Input>
                                        
                                    </template>
                                    
                                    <!-- If the given value is a String -->
                                    <InputNumber v-else-if="(typeof value == 'number')" type="text"  v-model.number="userAccountForm.metadata[name]" 
                                            :disabled="isLoadingAnything" class="w-100">
                                    </InputNumber>

                                    <template v-else-if="(typeof value == 'object')">
                                        
                                        <div class="clearfix">

                                            <!-- Edit Button -->
                                            <Button type="primary" size="small" class="float-right" @click.native="fetchUserAccounts()">
                                                <span>Edit</span>
                                            </Button>

                                        </div>
                                        
                                    </template>

                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>
                
                <template v-if="isEditing">

                    <!-- Save Changes Button -->
                    <FormItem v-if="!isSavingChanges">

                        <basicButton :disabled="(!userAccountHasChanged || isSavingChanges)" :loading="isSavingChanges" 
                                    :ripple="(userAccountHasChanged && !isSavingChanges)" type="success" size="large" 
                                    class="float-right mt-3" @click.native="handleSubmit()">
                            <span>{{ isSavingChanges ? 'Saving...' : 'Save Changes' }}</span>
                        </basicButton>

                    </FormItem>

                    <!-- If we are loading, Show Loader -->
                    <Loader v-show="isSavingChanges" class="mt-2">Saving user account...</Loader>

                </template>

                <template v-else>

                    <!-- Create Button -->
                    <FormItem v-if="!isCreating">

                        <basicButton :disabled="(!userAccountHasChanged || isCreating)" :loading="isCreating" 
                                    :ripple="(userAccountHasChanged && !isCreating)" type="success" size="large" 
                                    class="float-right mt-3" @click.native="handleSubmit()">
                            <span>{{ isCreating ? 'Creating...' : 'Create Instant Cart' }}</span>
                        </basicButton>

                    </FormItem>

                    <!-- If we are loading, Show Loader -->
                    <Loader v-show="isCreating" class="mt-2">Creating user account...</Loader>

                </template>

            </Form>

        </Drawer>
        
    </div>
</template>
<script>

    import userAccountMetadataTable from './userAccountMetadataTable.vue';
    import Loader from './../../../../../../../../../components/_common/loaders/default.vue';
    import drawerMixin from './../../../../../../../../../components/_mixins/drawer/main.vue';
    import basicButton from './../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        mixins: [ drawerMixin ],
        components: { userAccountMetadataTable, basicButton, Loader },
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
            index: {
                type: Number,
                default: null
            },
            userAccount: {
                type: Object,
                default: null
            }
        },
        data(){
            return{
                isCreating: false,
                isSavingChanges: false,
                userAccountFormRules: {
                    first_name: [
                        { required: true, message: 'Enter the user account first name', trigger: 'blur' },
                        { min: 3, message: 'User account first name is too short', trigger: 'change' },
                        { max: 50, message: 'User account first name is too long', trigger: 'change' }
                    ],
                    last_name: [
                        { required: true, message: 'Enter the user account last name', trigger: 'blur' },
                        { min: 3, message: 'User account last name is too short', trigger: 'change' },
                        { max: 50, message: 'User account last name is too long', trigger: 'change' }
                    ]
                },
                userAccountFormBeforeChanges: null,
                userAccountForm: null,

                serverErrorMessage: '',
                serverErrors: [],
            }
        },
        computed: {
            isLoadingAnything(){
                return (this.isCreating || this.isSavingChanges)
            },
            isEditing(){
                return this.userAccount ? true : false
            },
            okText(){
                //  If we have an user account then use "Save Changes" otherwise "Create User Account" as the ok text
                return this.userAccount ? 'Save Changes' : 'Create User Account';
            },
            drawerTitle(){
                if( this.userAccount ){
                    return 'Edit User Account';
                }else{
                    return 'Create User Account';
                }
            },
            userAccountHasChanged(){

                //  Check if the user account has been modified
                var status = !_.isEqual(this.userAccountForm, this.userAccountFormBeforeChanges);

                //  Notify the parent component of the change status
                this.$emit('unsavedChanges', status);
                
                return status;

            },
            userAccountUrl(){
                if( this.userAccount ){
                    return this.userAccount['_links']['self'].href;
                }
            },
            createUserAccountUrl(){
                return this.project['_links']['sce:user-account-create'].href;
            },
        },
        methods: {
            convertToString(value){
                return value.toString();
            },
            updateMetadata(name, value, type){
                
                //  If the given value is a boolean
                if(type == 'boolean'){
                    
                    value = (value == 'true') ? true : false;
                    
                }

                this.$set(this.userAccountForm.metadata, name, value);

            },
            setForm(){
                
                this.userAccountForm = _.cloneDeep(Object.assign({},
                    //  Set the default form details
                    {
                        first_name: '',
                        last_name: '',
                        mobile_number: '',
                        metadata: null,
                        
                        project_id: (this.project || {}).id,

                    //  Overide the default form details with the provided user account details
                    }, this.userAccount));

            },
            copyFormBeforeUpdate(){
                
                //  Clone the product
                this.userAccountFormBeforeChanges = _.cloneDeep( this.userAccountForm );

            },
            handleSubmit(){

                //  Reset the server errors
                this.resetErrors();

                //  Validate the user account form
                this.$refs['userAccountForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {
                        
                        //  If we are editing
                        if( this.isEditing ){

                            //  Attempt to save user account
                            this.saveUserAccount();

                        }else{

                            //  Attempt to create user account
                            this.createUserAccount();

                        }

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot update yet',
                            duration: 6
                        });
                    }
                })
            },
            createUserAccount() {

                //  Hold constant reference to the current Vue instance
                const self = this;

                //  Start loader
                this.isCreating = true;

                //  Notify parent that this component is creating
                this.$emit('isCreating', this.isCreating);
                
                /** Make an Api call to create the user account. We include the
                 *  user account details required for a new user account 
                 *  creation.
                 */
                let userAccountData = this.userAccountForm;

                return api.call('post', this.createUserAccountUrl, userAccountData)
                    .then(({data}) => {
                
                        console.log(data);

                        //  Stop loader
                        self.isCreating = false;

                        //  Notify parent that this component is not creating
                        self.$emit('isCreating', self.isCreating);
    
                        //  Notify parent of the user account created
                        self.$emit('createdUserAccount', data);

                        //  User Account created success message
                        self.$Message.success({
                            content: 'User account has been created!',
                            duration: 6
                        });

                        //  Reset the form
                        self.resetForm();
                        
                        self.closeDrawer();
                        
                    }).catch((response) => {
                
                        console.log(response);

                        //  Stop loader
                        self.isCreating = false;

                        //  Set the general error message
                        self.serverErrorMessage = (data || {}).message;

                        //  Notify parent that this component is not creating
                        self.$emit('isCreating', self.isCreating);

                });
            },
            saveUserAccount() {

                //  Hold constant reference to the current Vue instance
                const self = this;

                //  Start loader
                self.isSavingChanges = true;

                //  Notify parent that this component is saving data
                self.$emit('isSaving', self.isSavingChanges);

                /** Make an Api call to save the user account. We include the
                 *  user account details required for saving this user account
                 */
                let userAccountData = this.userAccountForm;

                return api.call('put', this.userAccountUrl, userAccountData)
                    .then(({data}) => {
                
                        console.log(data);

                        //  Stop loader
                        self.isSavingChanges = false;

                        //  Notify parent that this component is not saving data
                        self.$emit('isSaving', self.isSavingChanges);

                        self.$emit('savedUserAccount', data, self.index);

                        //  User Account updated success message
                        self.$Message.success({
                            content: 'User account has been updated!',
                            duration: 6
                        });

                        //  Reset the form
                        self.resetForm();
                        
                        self.copyFormBeforeUpdate();
                        
                        self.closeDrawer();
                        
                    }).catch((response) => {
                
                        console.log(response);

                        //  Stop loader
                        self.isSavingChanges = false;

                        //  Set the general error message
                        self.serverErrorMessage = (data || {}).message;

                        //  Notify parent that this component is not saving data
                        self.$emit('isSaving', self.isSavingChanges);

                });
            },
            resetErrors(){
                this.serverErrorMessage = '';
                this.serverErrors = [];
            },
            resetForm(){
                this.resetErrors();
                this.$refs['userAccountForm'].resetFields();
            }
        },
        created(){

            //  Set the form
            this.setForm();

            //  Copy the form before any changes are made
            this.copyFormBeforeUpdate();

        }
    }
</script>