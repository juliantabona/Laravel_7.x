<template>

    <Card :class="cardClasses" :style="cardStyles">
        
        <!-- Formatting Rule Title -->
        <div slot="title" class="cursor-pointer" @click="toggleExpansion()">
            
            <Row>

                <Col :span="14" class="d-flex">

                    <!-- Expand / Collapse Icon  -->
                    <Icon :type="arrowDirection" 
                          class="text-primary cursor-pointer mr-2" :size="20" 
                          :style="{ marginTop: '-3px' }" @click.stop="toggleExpansion()" />

                    <span class="single-draggable-item-title d-block font-weight-bold cut-text">{{ formattingRule.name }}</span>

                </Col>

                <Col class="d-flex" :span="10">
                
                    <!-- Failed Link Warning -->
                    <Poptip trigger="hover" width="350" placement="top" word-wrap>

                        <List slot="content" size="small">

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Active: </span>
                                <span v-if="formattingRule.active.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span class="cut-text">{{ formattingRule.active.text ? 'Yes' : 'No' }}</span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Name: </span>
                                <span class="cut-text">{{ formattingRule.name }}</span>
                            </ListItem>

                            <ListItem v-if="formattingRule.comment" class="list-item-comment">
                                <span>
                                    <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                                    <span class="font-weight-bold">Comment: </span><br>{{ formattingRule.comment }}
                                </span>
                            </ListItem>
                        
                        </List>

                        <Icon v-if="isValidFormattingRule" type="ios-information-circle-outline" class="text-primary mr-1" :style="{ marginTop: '-5px' }" size="30" />
                        <Icon v-else type="ios-alert-outline" class="text-danger mr-1" :style="{ marginTop: '-5px' }" size="30" />

                    </Poptip>

                    <!-- Active Status -->
                    <div :style="{ marginTop: '-4px' }">
                        <Tag v-if="formattingRule.active.code_editor_mode" type="border" color="cyan">Active Conditionally</Tag>
                        <Tag v-else type="border" :color="formattingRule.active.text ? 'green' : 'warning'">{{ formattingRule.active.text ? 'Active' : 'InActive' }}</Tag>
                    </div>

                </Col>
            </Row>
            
        </div>

        <!-- Formatting Rule Toolbar (Edit, Move, Delete Buttons) -->
        <div slot="extra">

            <div class="single-draggable-item-toolbox">

                <!-- Remove Formatting Rule Button  -->
                <Icon type="ios-trash-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleConfirmRemoveFormattingRule()" />

                <!-- Edit Formatting Rule Button  -->
                <Icon type="ios-create-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleEditFormattingRule()" />

                <!-- Copy Formatting Rule Button  -->
                <Icon type="ios-copy-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleCloneFormattingRule()"/>

                <!-- Move Formatting Rule Button  -->
                <Icon type="ios-move" class="single-draggable-item-icon dragger-handle mr-2" size="20" />
            
            </div> 

        </div>  

        <div v-show="isExpanded">

            <!-- Formatting Rule Details  -->
                
        </div>

        <!-- 
            MODAL TO CLONE / EDIT Formatting Rule
        -->
        <template v-if="isOpenManageFormattingRuleModal">

            <manageFormattingRuleModal
                :index="index"
                :screen="screen"
                :display="display"
                :builder="builder"
                :isCloning="isCloning"
                :isEditing="isEditing"
                :formattingRule="formattingRule"
                :formattingRules="formattingRules"
                @visibility="isOpenManageFormattingRuleModal = $event">
            </manageFormattingRuleModal>

        </template>

    </Card>

</template>

<script>

    import manageFormattingRuleModal from './../edit-formatting-rule/manageFormattingRuleModal.vue';

    export default {
        components: { manageFormattingRuleModal },
        props: {
            index: {
                type: Number,
                default:null
            },
            formattingRule: {
                type: Object,
                default:() => {}
            },
            formattingRules: {
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
                isOpenManageFormattingRuleModal: false,
                isExpanded: false,
                isEditing: false,
                isCloning: false
            }
        },
        computed: {
            cardStyles(){
                return {
                    borderLeft: '4px solid ' + this.formattingRule.hexColor
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
            isValidFormattingRule(){

                return true;

            }
        },
        methods: {
            toggleExpansion(){
                this.isExpanded = !this.isExpanded;
            },
            handleEditFormattingRule(){
                this.isCloning = false;
                this.isEditing = true;
                this.handleOpenManageFormattingRuleModal();
            },
            handleCloneFormattingRule() {
                this.isCloning = true;
                this.isEditing = false;
                this.handleOpenManageFormattingRuleModal();
            },
            handleConfirmRemoveFormattingRule(){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the formatting rule removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Formatting Rule',
                    onOk: () => { this.handleRemoveFormattingRule() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, self.formattingRule.name),
                                '". After this formatting rule is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveFormattingRule() {

                //  Remove formatting rule from list
                this.formattingRules.splice(this.index, 1);

                //  Formatting rule removed success message
                this.$Message.success({
                    content: 'Formatting rule removed!',
                    duration: 6
                });
            },
            handleOpenManageFormattingRuleModal(){
                this.isOpenManageFormattingRuleModal = true;
            }
        }
    }

</script>
