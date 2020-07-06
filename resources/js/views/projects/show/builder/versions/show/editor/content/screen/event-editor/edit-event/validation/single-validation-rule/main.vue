<template>

    <Card :class="cardClasses" :style="cardStyles">
        
        <!-- Validation Rule Title -->
        <div slot="title" class="cursor-pointer" @click="toggleExpansion()">
            
            <Row>

                <Col :span="14" class="d-flex">

                    <!-- Expand / Collapse Icon  -->
                    <Icon :type="arrowDirection" 
                          class="text-primary cursor-pointer mr-2" :size="20" 
                          :style="{ marginTop: '-3px' }" @click.stop="toggleExpansion()" />

                    <span class="single-draggable-item-title d-block font-weight-bold cut-text">{{ validationRule.name }}</span>

                </Col>

                <Col class="d-flex" :span="10">
                
                    <!-- Failed Link Warning -->
                    <Poptip trigger="hover" width="350" placement="top" word-wrap>

                        <List slot="content" size="small">

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Active: </span>
                                <span v-if="validationRule.active.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span class="cut-text">{{ validationRule.active.text ? 'Yes' : 'No' }}</span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Name: </span>
                                <span class="cut-text">{{ validationRule.name }}</span>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Message: </span>
                                <span v-if="validationRule.error_msg.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span class="cut-text">{{ validationRule.error_msg.text }}</span>
                                </template>
                            </ListItem>

                            <ListItem v-if="validationRule.comment" class="list-item-comment">
                                <span>
                                    <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                                    <span class="font-weight-bold">Comment: </span><br>{{ validationRule.comment }}
                                </span>
                            </ListItem>
                        
                        </List>

                        <Icon v-if="isValidValidationRule" type="ios-information-circle-outline" class="text-primary mr-1" :style="{ marginTop: '-5px' }" size="30" />
                        <Icon v-else type="ios-alert-outline" class="text-danger mr-1" :style="{ marginTop: '-5px' }" size="30" />

                    </Poptip>

                    <!-- Active Status -->
                    <div :style="{ marginTop: '-4px' }">
                        <Tag v-if="validationRule.active.code_editor_mode" type="border" color="cyan">Active Conditionally</Tag>
                        <Tag v-else type="border" :color="validationRule.active.text ? 'green' : 'warning'">{{ validationRule.active.text ? 'Active' : 'InActive' }}</Tag>
                    </div>

                </Col>
            </Row>
            
        </div>

        <!-- Validation Rule Toolbar (Edit, Move, Delete Buttons) -->
        <div slot="extra">

            <div class="single-draggable-item-toolbox">

                <!-- Remove Validation Rule Button  -->
                <Icon type="ios-trash-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleConfirmRemoveValidationRule()" />

                <!-- Edit Validation Rule Button  -->
                <Icon type="ios-create-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleEditValidationRule()" />

                <!-- Copy Validation Rule Button  -->
                <Icon type="ios-copy-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleCloneValidationRule()"/>

                <!-- Move Validation Rule Button  -->
                <Icon type="ios-move" class="single-draggable-item-icon dragger-handle mr-2" size="20" />
            
            </div> 

        </div>  

        <div v-show="isExpanded">

            <!-- Validation Rule Details  -->
                
        </div>

        <!-- 
            MODAL TO CLONE / EDIT Validation Rule
        -->
        <template v-if="isOpenManageValidationRuleModal">

            <manageValidationRuleModal
                :index="index"
                :screen="screen"
                :display="display"
                :builder="builder"
                :isCloning="isCloning"
                :isEditing="isEditing"
                :validationRule="validationRule"
                :validationRules="validationRules"
                @visibility="isOpenManageValidationRuleModal = $event">
            </manageValidationRuleModal>

        </template>

    </Card>

</template>

<script>

    import manageValidationRuleModal from './../edit-validation-rule/manageValidationRuleModal.vue';

    export default {
        components: { manageValidationRuleModal },
        props: {
            index: {
                type: Number,
                default:null
            },
            validationRule: {
                type: Object,
                default:() => {}
            },
            validationRules: {
                type: Array,
                default:() => []
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            builder: {
                type: Object,
                default: () => {}
            }
        },
        data(){

            return {
                isOpenManageValidationRuleModal: false,
                isExpanded: false,
                isEditing: false,
                isCloning: false
            }
        },
        computed: {
            cardStyles(){
                return {
                    borderLeft: '4px solid ' + this.validationRule.hexColor
                }
            },
            cardClasses(){
                return [
                    'single-draggable-item', 
                    (this.isExpanded ? 'active' : ''), 'mb-2'
                ]
            },
            arrowDirection(){
                return this.isExpanded ? 'ios-arrow-down' : 'ios-arrow-forward';
            },
            isValidValidationRule(){

                return true;

            }
        },
        methods: {
            toggleExpansion(){
                this.isExpanded = !this.isExpanded;
            },
            handleEditValidationRule(){
                this.isCloning = false;
                this.isEditing = true;
                this.handleOpenManageValidationRuleModal();
            },
            handleCloneValidationRule() {
                this.isCloning = true;
                this.isEditing = false;
                this.handleOpenManageValidationRuleModal();
            },
            handleConfirmRemoveValidationRule(){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the validation rule removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Validation Rule',
                    onOk: () => { this.handleRemoveValidationRule() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, self.validationRule.name),
                                '". After this validation rule is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveValidationRule() {

                //  Remove validation rule from list
                this.validationRules.splice(this.index, 1);

                //  Validation rule removed success message
                this.$Message.success({
                    content: 'Validation rule removed!',
                    duration: 6
                });
            },
            handleOpenManageValidationRuleModal(){
                this.isOpenManageValidationRuleModal = true;
            }
        }
    }

</script>
