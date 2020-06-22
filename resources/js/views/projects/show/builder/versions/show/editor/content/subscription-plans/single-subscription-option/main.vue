<template>

    <Card :class="cardClasses" :style="cardStyles">
        
        <!-- Subscription Option Title -->
        <div slot="title" class="cursor-pointer" @click="toggleExpansion()">
            
            <Row>

                <Col :span="14" class="d-flex">

                    <!-- Expand / Collapse Icon  -->
                    <Icon v-if="isVariable" :type="arrowDirection" 
                        class="text-primary cursor-pointer mr-2" size="20" 
                        :style="{ marginTop: '-3px' }" @click.stop="toggleExpansion()" />

                    <!-- Subscription Option Name Label  -->
                    <span class="single-draggable-item-title d-block font-weight-bold cut-text" :style="{ height: '20px' }">
                        {{ getSubscriptionOptionNumber + '. ' }}
                        {{ localSubscriptionOption.name }}
                    </span>

                </Col>

                <Col :span="10">

                    <div v-if="isVariable" :style="{ marginTop: '-4px' }">

                        <!-- Variable options count  -->
                        <Tag v-if="optionsExist" color="cyan">
                            {{ totalOptions }} {{ totalOptions == 1 ? 'Option' : 'Options' }}
                        </Tag>

                        <Tag v-else type="border" color="warning" class="d-flex" :style="{ maxWidth: '115px'  }">
                            <Icon type="ios-alert-outline" size="20" class="mr-1" />
                            <span>No Options</span>
                        </Tag>

                    </div>

                    <div v-else :style="{ marginTop: '-4px' }">

                        <!-- Variable option details  -->
                        <Tag type="border" color="success">
                            {{ localSubscriptionOption.frequency }} @
                            P{{ localSubscriptionOption.price }}
                        </Tag>

                    </div>

                </Col>

            </Row>
            
        </div>

        <!-- Subscription Option Toolbar (Edit, Move, Delete Buttons) -->
        <div slot="extra">

            <div class="single-draggable-item-toolbox">

                <!-- Remove Subscription Option Button  -->
                <Icon type="ios-trash-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleConfirmRemoveSubscriptionOption()" />

                <!-- Edit Subscription Option Button  -->
                <Icon type="ios-create-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleEditSubscriptionOption()" />

                <!-- Copy Subscription Option Button  -->
                <Icon type="ios-copy-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleCloneSubscriptionOption()"/>

                <!-- Move Subscription Option Button  -->
                <Icon type="ios-move" class="single-draggable-item-icon dragger-handle mr-2" size="20" />
            
            </div> 

        </div>  

        <template v-if="isVariable">

            <div v-show="isExpanded">

                <!-- Subscription Option Instruction  -->
                <div class="d-flex">
                    <Icon type="ios-arrow-round-down" :size="20" class="mr-1" />
                    <span :class="instructionClasses" :style="{ height: '20px' }">
                        {{ localSubscriptionOption.instruction || 'No Instructions' }}
                    </span>
                </div>
                
                <!-- Draggable subscription options -->     
                <draggable
                    :style="draggableStyles"
                    :list="localSubscriptionOption.options"
                    @start="drag=true" 
                    @end="drag=false" 
                    :options="{
                        handle:'.dragger-handle',
                        group:'subscription-options', 
                        draggable:'.single-draggable-item',
                    }">

                    <!-- Single subscription option -->
                    <singleSubscriptionOption v-for="(subSubscriptionOption, index) in localSubscriptionOption.options" 
                        @saveChanges="handleSavedSubscriptionOption($event, index)"
                        :subscriptionOptions="localSubscriptionOption.options"
                        :key="subSubscriptionOption.name +'_'+ index"
                        :subscriptionOption="subSubscriptionOption"
                        :builder="builder"
                        :index="index">
                    </singleSubscriptionOption>

                    <!-- No Options Alert -->
                    <Alert v-if="!optionsExist" type="info" class="w-100 mt-2 mb-2">No options found - <small>Drag And Drop</small></Alert>

                    <div class="clearfix mb-2">

                        <!-- Add Subscription Options Button -->
                        <basicButton :type="addButtonType" size="small" icon="ios-add" :showIcon="true"
                                    class="float-right" iconDirection="left" :ripple="!optionsExist"
                                    @click.native="handleAddSubscriptionOptionModal()">
                            <span>Add Option</span>
                        </basicButton>

                    </div>
                    
                </draggable>
                    
            </div>

        </template>

        <!-- 
            MODAL TO ADD / CLONE / EDIT SUBSCRIPTION OPTION
        -->
        <template v-if="isOpenManageSubscriptionOptionModal">

            <manageSubscriptionOptionModal
                :index="index"
                :builder="builder"
                :isCloning="isCloning"
                :isEditing="isEditing"
                :subscriptionOptions="subscriptionOptions"
                :subscriptionOption="localSubscriptionOption"
                @saveChanges="handleSavedSubscriptionOption($event)"
                @visibility="isOpenManageSubscriptionOptionModal = $event">
            </manageSubscriptionOptionModal>

        </template>

    </Card>

</template>

<script>

    import draggable from 'vuedraggable';

    import manageSubscriptionOptionModal from './manageSubscriptionOptionModal.vue';

    import basicButton from './../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { draggable, manageSubscriptionOptionModal, basicButton },
        props: {
            index: {
                type: Number,
                default: null
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

            return {
                isOpenManageSubscriptionOptionModal: false,
                localSubscriptionOption: this.subscriptionOption,
                isExpanded: false,
                isEditing: false,
                isCloning: false
            }
        },
        computed: {
            totalOptions(){
                return ((this.localSubscriptionOption || {}).options || {}).length;
            },
            optionsExist(){
                return this.totalOptions ? true : false;
            },
            isVariable(){
                return this.localSubscriptionOption.type == 'variable';
            },
            addButtonType(){
                return this.optionsExist ? 'default' : 'success';
            },
            cardStyles(){
                return {
                    borderLeft: '4px solid ' + this.localSubscriptionOption.hexColor
                }
            },
            cardClasses(){
                return [
                    'single-draggable-item', 
                    (this.isVariable && this.isExpanded ? 'active' : ''), 'mb-2'
                ]
            },
            draggableStyles(){
                return  this.optionsExist ? {} : {
                    padding: '10px',
                    borderRadius: '9px',
                    background: '#f9f9f9',
                    border: '1px dashed #c5c5c5'
                }
            },
            instructionClasses(){
                return ['d-block', 'font-italic', 'mb-2', 'cut-text',
                    this.localSubscriptionOption.instruction ? 'text-primary' : ''
                ];
            },
            arrowDirection(){
                return this.isExpanded ? 'ios-arrow-down' : 'ios-arrow-forward';
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
            toggleExpansion(){
                this.isExpanded = !this.isExpanded;
            },
            handleSavedSubscriptionOption(updatedSubscriptionOption, index){

                if( index != undefined ){

                    this.$set(this.localSubscriptionOption.options, index, updatedSubscriptionOption);
                    this.$emit('saveChanges', this.localSubscriptionOption);

                }else{

                    this.localSubscriptionOption = updatedSubscriptionOption;
                    this.$emit('saveChanges', this.localSubscriptionOption);
                        
                }

            },
            handleAddSubscriptionOptionModal(){
                this.isCloning = false;
                this.isEditing = false;
                this.handleOpenManageSubscriptionOptionModal();
            },
            handleEditSubscriptionOption(){
                this.isCloning = false;
                this.isEditing = true;
                this.handleOpenManageSubscriptionOptionModal();
            },
            handleCloneSubscriptionOption() {
                this.isCloning = true;
                this.isEditing = false;
                this.handleOpenManageSubscriptionOptionModal();
            },
            handleConfirmRemoveSubscriptionOption(){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the subscription option removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Subscription Option',
                    onOk: () => { this.handleRemoveSubscriptionOption() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, self.localSubscriptionOption.name),
                                '". After this subscription option is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveSubscriptionOption() {

                //  Remove subscription option from list
                this.subscriptionOptions.splice(this.index, 1);

                //  Subscription option removed success message
                this.$Message.success({
                    content: 'Subscription option removed!',
                    duration: 6
                });
            },
            handleOpenManageSubscriptionOptionModal(){
                this.isOpenManageSubscriptionOptionModal = true;
            }
        }
    }

</script>
