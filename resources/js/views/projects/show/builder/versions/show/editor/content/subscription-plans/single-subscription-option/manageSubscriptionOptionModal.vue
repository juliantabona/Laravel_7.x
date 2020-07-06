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
            :title="modalTitle"
            v-model="modalVisible"
            @on-visible-change="detectClose">

            <Alert v-if="isEditing" show-icon>Editing "<span class="font-weight-bold">{{ subscriptionOption.name }}</span>"</Alert>

            <Alert v-else-if="isCloning" show-icon>Cloning "<span class="font-weight-bold">{{ subscriptionOption.name }}</span>"</Alert>

            <!-- Form -->
            <Form ref="subscriptionOptionForm" :model="subscriptionOptionForm" :rules="subscriptionOptionFormRules">

                <Row :gutter="12" class="mb-2">

                    <Col :span="12">

                        <!-- Select Type -->
                        <FormItem prop="type" class="mb-1">
                            <span class="d-flex">
                                <span class="font-weight-bold mr-2">Type: </span>
                                <Select v-model="subscriptionOptionForm.type" class="w-100" placeholder="Select type">
                                    <Option v-for="subscriptionType in subscriptionTypes" :value="subscriptionType.value" 
                                            :key="subscriptionType.value">
                                        {{ subscriptionType.name }}
                                    </Option>
                                </Select>
                            </span>
                        </FormItem>
                    
                    </Col>

                    <Col :span="12">
                
                        <!-- Select Active State -->
                        <FormItem prop="active" class="mb-1">
                            <span>
                                <span class="font-weight-bold">Active: </span>
                                <i-Switch v-model.number="subscriptionOptionForm.active" size="small"></i-Switch>
                            </span>
                        </FormItem>
                    
                    </Col>

                </Row>

                <!-- Enter Name -->
                <FormItem prop="name" class="mb-2">
                    <Input  type="text" v-model="subscriptionOptionForm.name" :placeholder="'Package ' + getSubscriptionOptionNumber" 
                            maxlength="50" show-word-limit @keyup.enter.native="handleSubmit()" v-focus="'input'">
                            <span slot="prepend">Name</span>
                    </Input>
                </FormItem>

                <template v-if="subscriptionOptionForm.type == 'variable'">

                    <!-- Enter Instruction -->
                    <FormItem prop="instruction" class="mb-2">
                        <Input  type="textarea" v-model="subscriptionOptionForm.instruction" 
                                placeholder="Enter instructions" show-word-limit>
                        </Input>
                    </FormItem>

                </template>

                <template v-else>
                
                    <!-- Enter Price -->
                    <FormItem prop="price" class="mb-2">
                        <span class="d-flex">
                            <span class="font-weight-bold mr-2">Price: </span>
                            <InputNumber v-model.number="subscriptionOptionForm.price" 
                                    placeholder="Enter price..." class="w-100">
                            </InputNumber>
                        </span>
                    </FormItem>
                    
                    <!-- Select Frequency -->
                    <FormItem prop="frequency" class="mb-1">
                        <span class="d-flex">
                            <span class="font-weight-bold mr-2">Frequency: </span>
                            <Select v-model="subscriptionOptionForm.frequency" class="w-100" placeholder="Select frequency">
                                <Option v-for="frequencyType in frequencyTypes" :value="frequencyType.value" 
                                        :key="frequencyType.value">
                                    {{ frequencyType.name }}
                                </Option>
                            </Select>
                        </span>
                    </FormItem>

                </template>

            </Form>

            <div class="border-top pt-3">
                <span class="d-inline-block mr-2">
                    <span class="font-weight-bold">Highlighter</span>: 
                    <ColorPicker v-model="subscriptionOptionForm.hexColor" recommend></ColorPicker>
                </span>
            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">{{ modalOkBtnText }}</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    import modalMixin from './../../../../../../../../../../components/_mixins/modal/main.vue';

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../../../../mixin.js').default;

    export default {
        mixins: [modalMixin, customMixin],
        props: {
            index: {
                type: Number,
                default: null
            },
            isCloning: {
                type: Boolean,
                default: false
            },
            isEditing: {
                type: Boolean,
                default: false
            },
            builder: {
                type: Object,
                default: null
            },
            subscriptionOption: {
                type: Object,
                default: null
            },
            subscriptionOptions: {
                type: Array,
                default: () => []
            }
        },
        data(){

            //  Custom validation to detect matching names
            const uniqueNameValidator = (rule, value, callback) => {
                

                if( !this.isCloning && this.subscriptionOption != null ){
                    var subscriptionOptions = this.subscriptionOption.options;
                }else{
                    var subscriptionOptions = this.subscriptionOptions;
                }

                //  Check if subscription plans with the same name exist
                var similarNamesExist = subscriptionOptions.filter( (subscriptionOption, index) => { 
                    
                    //  If we are editing
                    if( this.isEditing ){

                        //  Skip checking the current subscription option
                        if( this.index == index ){
                            return false;
                        }

                    }
                    
                    //  If the given value matches the subscription option name
                    return (value == subscriptionOption.name);
                
                }).length;

                //  If subscription plans with a similar name exist
                if (similarNamesExist) {
                    callback(new Error('This subscription option name is already in use'));
                } else {
                    callback();
                }
            };

            return {
                localSubscriptionOption: this.subscriptionOption,
                subscriptionOptionForm: null,
                subscriptionOptionFormRules: {
                    name: [
                        { required: true, message: 'Please enter your subscription option name', trigger: 'blur' },
                        { min: 3, message: 'Subscription option name is too short', trigger: 'change' },
                        { max: 50, message: 'Subscription option name is too long', trigger: 'change' },
                        { validator: uniqueNameValidator, trigger: 'change' }
                    ],
                    type: [
                        { required: true, message: 'Select subscription option plan type', trigger: 'change' }
                    ],
                    frequency: [
                        { required: true, message: 'Select subscription option frequency', trigger: 'change' }
                    ],
                },
                subscriptionTypes: [
                    {
                        name: 'Simple',
                        value: 'simple'
                    },
                    {
                        name: 'Variable',
                        value: 'variable'
                    }
                ],
                frequencyTypes: [
                    {
                        name: 'Daily',
                        value: 'daily'
                    },
                    {
                        name: 'Weekly',
                        value: 'weekly'
                    },
                    {
                        name: 'Monthly',
                        value: 'monthly'
                    },
                    {
                        name: 'Yearly',
                        value: 'yearly'
                    },
                ],
                
            }
        },
        computed: {
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Subscription Option';

                }else if( this.isCloning ){

                    return 'Clone Subscription Option';
                
                }else{

                    return 'Add Subscription Option';

                }

            },
            modalOkBtnText(){

                if( this.isEditing ){

                    return 'Save Changes';

                }else if( this.isCloning ){

                    return 'Clone';
                
                }else{

                    return 'Add Option';

                }

            },
            getSubscriptionOptionNumber(){
                /**
                 *  Returns the subscription option number. We use this as we list the subscription options.
                 *  It works like a counter.
                 */
                return (this.index != null ? this.index + 1 : '');
            }
        },
        methods: {
            getSubscriptionOptionForm(){
                 
                var overides = {};

                //  If we are editing or cloning
                if( this.isEditing || this.isCloning ){
                    
                    //  Set the overide data
                    overides = this.localSubscriptionOption;

                }
                
                return _.cloneDeep( 
                    
                    Object.assign({},
                    //  Set the default form details
                    {
                        name: '',
                        price: 0,
                        options: [],
                        active: true,
                        type: 'simple',
                        instruction: '',
                        frequency: 'daily',
                        hexColor: '#CECECE',
                        id: this.generateSubscriptionOptionId(),

                    //  Overide the default form details with the provided project details
                    }, overides)
                );

            },
            handleSubmit(){
                
                //  Validate the subscription option form
                this.$refs['subscriptionOptionForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        if( this.isEditing ){
                        
                            this.handleEditSubscriptionOption();

                        }else if( this.isCloning ){
                        
                            this.handleCloneSubscriptionOption();

                        }else{

                            //  Add the subscription plan
                            this.handleAddNewSubscriptionOption();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your subscription option yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditSubscriptionOption(){

                this.$emit('saveChanges', this.subscriptionOptionForm);

                this.$Message.success({
                    content: 'Subscription option updated!',
                    duration: 6
                });

            },
            handleCloneSubscriptionOption(){

                //  Update the subscription option id
                this.subscriptionOptionForm.id = this.generateSubscriptionOptionId();

                //  Add the cloned subscription option to the rest of the other subscription options
                this.subscriptionOptions.push(this.subscriptionOptionForm);

                this.$Message.success({
                    content: 'Subscription option cloned!',
                    duration: 6
                });

            },
            handleAddNewSubscriptionOption(){

                //  If the index was provided
                if( this.index != undefined && this.index != null ){

                    //  Add the new subscription option to the rest of the other subscription options
                    var newIndex = this.subscriptionOptions[this.index].options.length;

                    this.$set(this.subscriptionOptions[this.index].options, newIndex, this.subscriptionOptionForm);

                //  If the index was not provided
                }else{

                    //  Add the new subscription option to the rest of the other subscription options
                    var newIndex = this.subscriptionOptions.length;
                    this.$set(this.subscriptionOptions, newIndex, this.subscriptionOptionForm);

                }

                this.$Message.success({
                    content: 'Subscription option added!',
                    duration: 6
                });

            },
            generateSubscriptionOptionId(){
                return 'subscription_option_' + Date.now();
            }
        },
        created(){
            this.subscriptionOptionForm = this.getSubscriptionOptionForm();
        }
    }
</script>