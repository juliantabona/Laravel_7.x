<template>
    <div>
        <!-- Modal 

             Note: modalVisible and detectClose() are imported from the modalMixin.
             They are used to allow for opening and closing the modal properly
             during the v-if conditional statement of the parent component. It
             is important to note that <Modal> does not open/close well with
             v-if statements by default, therefore we need to add additional
             functionality to enhance the experience. Refer to modalMixin.
        -->
        <Modal
            :width="600"
            :title="modalTitle"
            v-model="modalVisible"
            @on-visible-change="detectClose">
                        
            <!-- Heading -->
            <Divider orientation="left" class="font-weight-bold">Subscription Plan Details</Divider>

            <Alert v-if="isEditing" show-icon>Editing "<span class="font-weight-bold">{{ subscriptionPlan.name }}</span>"</Alert>

            <Alert v-else-if="isCloning" show-icon>Cloning "<span class="font-weight-bold">{{ subscriptionPlan.name }}</span>"</Alert>

            <!-- Form -->
            <Form ref="subscriptionPlanForm" :model="subscriptionPlanForm" :rules="subscriptionPlanFormRules">

                <!-- Enter Name -->
                <FormItem prop="name">
                    <Input  type="text" v-model="subscriptionPlanForm.name" placeholder="Home" maxlength="50" 
                            show-word-limit @keyup.enter.native="handleSubmit()" v-focus="'input'">
                            <span slot="prepend">Name</span>
                    </Input>
                </FormItem>

                <!-- Enter Instruction -->
                <FormItem prop="instruction">
                    <Input  type="textarea" v-model="subscriptionPlanForm.instruction" 
                            placeholder="Enter instructions" show-word-limit>
                    </Input>
                </FormItem>

            </Form>
    
            <!-- Options -->
            <Divider orientation="left" class="font-weight-bold mb-0">Options</Divider>

            <!-- No Options Alert -->
            <Alert v-if="!optionsExist" type="info" class="mb-2">No subscription options found</Alert>

            <div class="clearfix mb-2">

                <!-- Add Subscription Options Button -->
                <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                             class="float-right" iconDirection="left" :ripple="!optionsExist"
                             @click.native="handleAddSubscriptionOptionModal()">
                    <span>Add Option</span>
                </basicButton>

            </div>

            <!-- Draggable subscription options -->
            <div v-if="optionsExist" class="border-top py-2">
                    
                <draggable 
                    :list="subscriptionPlanForm.options"
                    @start="drag=true" 
                    @end="drag=false" 
                    :options="{
                        handle:'.dragger-handle',
                        group:'subscription-options', 
                        draggable:'.single-draggable-item',
                    }">

                    <!-- Single subscription option  -->
                    <singleSubscriptionOption v-for="(subscriptionPlanOption, index) in subscriptionPlanForm.options" 
                        @saveChanges="handleSavedSubscriptionOption($event, index)"
                        :subscriptionOptions="subscriptionPlanForm.options"
                        :key="subscriptionPlanOption.name +'_'+ index"
                        :subscriptionOption="subscriptionPlanOption"
                        :builder="builder"
                        :index="index">
                    </singleSubscriptionOption>

                </draggable>

            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">{{ modalOkBtnText }}</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>

        <!-- 
            MODAL TO ADD / CLONE / EDIT SUBSCRIPTION OPTION
        -->
        <template v-if="isOpenManageSubscriptionOptionModal">

            <manageSubscriptionOptionModal
                :builder="builder"
                :subscriptionOptions="subscriptionPlanForm.options"
                @saveChanges="handleSavedSubscriptionOption($event)"
                @visibility="isOpenManageSubscriptionOptionModal = $event">
            </manageSubscriptionOptionModal>

        </template>

    </div>
</template>
<script>

    import draggable from 'vuedraggable';

    import basicButton from './../../../../../../../../../components/_common/buttons/basicButton.vue';

    //  Get the modal mixin data
    import modalMixin from './../../../../../../../../../components/_mixins/modal/main.vue';

    import manageSubscriptionOptionModal from './single-subscription-option/manageSubscriptionOptionModal.vue';

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../../../mixin.js').default;

    export default {
        mixins: [modalMixin, customMixin],
        components: { draggable, basicButton, manageSubscriptionOptionModal },
        props: {
            subscriptionPlan: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default:() => {}
            },
            index: {
                type: Number,
                default: 0
            },
            isCloning: {
                type: Boolean,
                default: false
            }
        },
        data(){

            //  Custom validation to detect matching subscription plan names
            const uniqueNameValidator = (rule, value, callback) => {

                const self = this;

                //  Check if subscription plans with the same name exist
                var similarNamesExist = this.builder.subscription_plans.filter( (subscriptionPlan, index) => {
                    
                    //  If we are editing
                    if( this.isEditing ){

                        //  Skip checking the current subscription plan
                        if( this.index == index ){
                            return false;
                        }

                    }

                    //  If the given value matches the subscription plan name
                    return (value == subscriptionPlan.name);

                }).length;

                //  If we are not editing and subscription plans with a similar name exist
                if (similarNamesExist) {
                    callback(new Error('This subscription plan name is already in use'));
                } else {
                    callback();
                }
            };

            return {
                subscriptionPlanForm: null,
                subscriptionPlanFormRules: {
                    name: [
                        { required: true, message: 'Please enter your subscription plan name', trigger: 'blur' },
                        { min: 3, message: 'Subscription plan name is too short', trigger: 'change' },
                        { max: 50, message: 'Subscription plan name is too long', trigger: 'change' },
                        { validator: uniqueNameValidator, trigger: 'change' }
                    ]
                },
                isOpenManageSubscriptionOptionModal: false,
            }
        },
        computed: {
            optionsExist(){
                return ((this.subscriptionPlanForm || {}).options || {}).length ? true : false;
            },
            addButtonType(){
                return this.optionsExist ? 'primary' : 'success';
            },
            isEditing(){
                
                //  If we have a subscription plan provided, but we are not cloning, then we are editing
                return !this.isCloning && this.subscriptionPlan;

            },
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Subscription Plan';

                }else if( this.isCloning ){

                    return 'Clone Subscription Plan';
                
                }else{

                    return 'Add Subscription Plan';

                }

            },
            modalOkBtnText(){

                if( this.isEditing ){

                    return 'Save Changes';

                }else if( this.isCloning ){

                    return 'Clone';
                
                }else{

                    return 'Add Subscription Plan';

                }

            },
            totalSubscriptionPlans(){
                return this.builder.subscription_plans.length;
            }
        },
        methods: {

            handleSavedSubscriptionOption(updatedSubscriptionOption, index){
                
                this.$set(this.subscriptionPlanForm.options, index, updatedSubscriptionOption);

            },
            getSubscriptionPlanForm(){

                return Object.assign({},
                    //  Set the default form details
                    {
                        id: this.generateSubscriptionPlanId(),
                        name: '',
                        instruction: '',
                        options: []

                    //  Overide the default form details with the provided subscription plan details
                    }, this.subscriptionPlan);

            },
            handleSubmit(){
                
                //  Validate the subscription plan form
                this.$refs['subscriptionPlanForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        if( this.isEditing ){
                        
                            this.handleEditSubscriptionPlan();

                        }else if( this.isCloning ){
                        
                            this.handleCloneSubscriptionPlan();

                        }else{

                            //  Add the subscription plan
                            this.handleAddNewSubscriptionPlan();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your subscription plan yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditSubscriptionPlan(){
                
                this.$emit('saveChanges', this.subscriptionPlanForm);

                this.$Message.success({
                    content: 'Subscription plan updated!',
                    duration: 6
                });
            },
            handleCloneSubscriptionPlan(){

                //  Update the subscription plan id
                this.subscriptionPlanForm.id = this.generateSubscriptionPlanId();

                //  Add the cloned subscription plan to the rest of the other subscription plans
                this.builder.subscription_plans.push(this.subscriptionPlanForm);

                this.$Message.success({
                    content: 'Subscription plan cloned!',
                    duration: 6
                });

            },
            handleAddNewSubscriptionPlan(){

                //  Add the new subscription plan to the rest of the other subscription plans
                this.builder.subscription_plans.push(this.subscriptionPlanForm);

                this.$Message.success({
                    content: 'Subscription plan added!',
                    duration: 6
                });

            },
            generateSubscriptionPlanId(){
                return 'subscription_plan_' + Date.now();
            },
            handleAddSubscriptionOptionModal(){
                this.handleOpenManageSubscriptionOptionModal();
            },
            handleOpenManageSubscriptionOptionModal(){
                this.isOpenManageSubscriptionOptionModal = true;
            }
        },
        created(){
            this.subscriptionPlanForm = this.getSubscriptionPlanForm();
        }
    }
</script>