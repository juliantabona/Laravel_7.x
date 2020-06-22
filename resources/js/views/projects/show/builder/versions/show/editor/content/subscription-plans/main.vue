<template>

    <Card :bordered="false" :style="{ overflow: 'inherit' }">

        <!-- Main Heading -->  
        <Divider orientation="left">
            <span class="font-weight-bold text-dark">Subcription Plans</span>
        </Divider>

        <div class="clearfix mb-4">

            <!-- Create Subscription Button -->
            <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                         class="float-right" iconDirection="left" :ripple="!subcriptionPlansExist"
                         @click.native="handleAddSubscriptionPlan()">
                <span>Create Subscription Plan</span>
            </basicButton>

        </div>

        <!-- Subscription Plans -->
        <Table :columns="columns" :data="builder.subscription_plans" :style="{ overflow: 'inherit' }" 
                class="mb-5" size="small" max-height="300" no-data-text="No Subscription Plans Found">

            <!-- Subscription Plan Name -->
            <template slot-scope="{ row }" slot="name">
                
                <strong>{{ row.name }}</strong>

            </template>

            <!-- Subscription Plan Status -->
            <template slot-scope="{ row }" slot="status">
                
                <i-Switch v-model="row.active" @on-change="updateSubscriptionPlanData(row, index)"></i-Switch>

            </template>

            <!-- Subscription Plan Action -->
            <template slot-scope="{ row, index }" slot="action">
                <Button type="primary" size="small" class="mr-1" 
                        @click="handleEditSubscriptionPlan(index)">Edit</Button>
                <Button type="default" size="small" class="mr-1" 
                        @click="handleCloneSubscriptionPlan(index)">Clone</Button>
                <Button type="error" size="small" @click="handleConfirmRemoveSubscriptionPlan(index)">Delete</Button>
            </template>

        </Table>

        <!-- 
            MODAL TO ADD / CLONE / EDIT SUBSCRIPTION PLAN
        -->
        <template v-if="isOpenAddSubscriptionPlanModal">

            <manageSubscriptionPlanModal
                :index="index"
                :builder="builder"
                :isCloning="isCloning"
                :subscriptionPlan="subscriptionPlan"
                @saveChanges="handleSaveChanges($event)"
                @visibility="isOpenAddSubscriptionPlanModal = $event">
            </manageSubscriptionPlanModal>

        </template>

    </Card>

</template>

<script>

    import manageSubscriptionPlanModal from './manageSubscriptionPlanModal.vue';
    import basicButton from './../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { manageSubscriptionPlanModal, basicButton },
        props: {
            builder: {
                type: Object,
                default: null
            },
        },
        data(){

            return {
                index: null,
                isCloning: false,
                subscriptionPlan: null,
                isOpenAddSubscriptionPlanModal: false,
                subscriptionPlanTypes: [
                    { name: 'Simple', value: 'simple'},
                    { name: 'Variable', value: 'variable'},
                ],
                columns: [
                    {
                        title: 'Name',
                        slot: 'name',
                        key: 'name'
                    },
                    {
                        width: 100,
                        title: 'Status',
                        slot: 'status',
                        key: 'active'
                    },
                    {
                        title: 'Action',
                        slot: 'action'
                    }
                ]
            }
        },
        computed: {
            subcriptionPlansExist(){
                return this.builder.subscription_plans.length ? true : false;
            },
            addButtonType(){
                return this.subcriptionPlansExist ? 'primary' : 'success';
            }
        },
        methods: {
            /** Note that the table data attribute does not offer two-way binding, this means that when
             *  we edit the "row" data e,g "row.name" or "row.type", we are only editing the local 
             *  value within the child <Table> component. This means that we are not updating the 
             *  subscription_plans data of the current component. To do so, we must manually listen 
             *  for changes on each input or select field, then update the subscription_plans.
             */
            updateSubscriptionPlanData(subscriptionPlan, index){
                this.$set(this.builder.subscription_plans, index, subscriptionPlan);
            },
            handleSaveChanges(updatedSubscriptionPlan){
                this.$set(this.builder.subscription_plans, this.index, updatedSubscriptionPlan);
            },
            handleConfirmRemoveSubscriptionPlan(index){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the subscription plan removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Subscription Plan',
                    onOk: () => { this.handleRemoveSubscriptionPlan(index) },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, (self.builder.subscription_plans[index] || {}).name),
                                '". After this subscription Plan is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleAddSubscriptionPlan(index){
                this.index = 0;
                this.isCloning = false;
                this.subscriptionPlan = null;
                this.handleOpenAddSubscriptionPlanModal();
            },
            handleRemoveSubscriptionPlan(index){
                this.builder.subscription_plans.splice(index, 1);

                this.$Message.success({
                    content: 'Subscription plan removed!',
                    duration: 6
                });
            },
            handleEditSubscriptionPlan(index){
                this.index = index;
                this.isCloning = false;
                this.subscriptionPlan = this.builder.subscription_plans[index];
                this.handleOpenAddSubscriptionPlanModal();
            },
            handleCloneSubscriptionPlan(index){
                this.index = index;
                this.isCloning = true;
                this.subscriptionPlan = this.builder.subscription_plans[index];
                this.handleOpenAddSubscriptionPlanModal();
            },
            handleOpenAddSubscriptionPlanModal(subscriptionPlan){
                //  this.subscriptionPlan = subscriptionPlan;
                this.isOpenAddSubscriptionPlanModal = true;
            }
        }
    }
</script>
