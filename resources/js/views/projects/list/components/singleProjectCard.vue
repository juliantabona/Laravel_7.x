<template>

    <Card @mouseover.native="isHovering = true"
          @mouseout.native="isHovering = false"
          @click.native="navigateToViewProject()"
          class="sce-mini-card cursor-pointer mb-3" >

        <Row>

            <Col :span="24">

                <div class="d-flex pb-2">

                    <!-- Project Logo -->
                    <div :class="['ivu-avatar', 'ivu-avatar-square', 'ivu-avatar-default']" :style="avatarStyles">
                        <span class="font-weight-bold">{{ project.name | firstLetter}}</span>
                    </div>

                    <!-- Project Name: Note "firstLetter" filter is registered as a custom mixin -->
                    <span class="cut-text font-weight-bold mt-2 ml-2">{{ project.name }}</span>

                </div>

                <div class="sce-mini-card-body mb-3 py-2 pl-2 pr-5">

                    <span class="d-inline-block">
                        <Badge :text="statusText" :status="status"></Badge>
                        <!-- If we are offline and have a reason provided -->
                        <Poptip v-if="!project.online && project.offline_message" trigger="hover" :content="project.offline_message" word-wrap width="300">
                            <!-- Show the info icon with the information of why we are offline -->
                            <Icon type="ios-information-circle-outline" :size="16" />
                        </Poptip>
                    </span>

                    <span class="d-inline-block">
                        <span>Version: </span><span class="font-weight-bold">{{ activeVersionNumber }}</span>
                        <!-- If we have the version description -->
                        <Poptip v-if="versionDescription" trigger="hover" :content="versionDescription" word-wrap width="300">
                            <!-- Show the info icon with the information from the version description -->
                            <Icon type="ios-information-circle-outline" :size="16" />
                        </Poptip>
                    </span>

                    <span class="d-inline-block">
                        <span>Dial: </span><span class="font-weight-bold">{{ primaryShortCode }}</span>
                        <!-- Show the short code details -->
                        <Poptip trigger="hover" word-wrap width="300">

                            <div slot="content" class="py-2" :style="{ lineHeight: 'normal' }">
                                <p>
                                    <span>Dedicated Code:</span> <span class="font-weight-bold">{{ dedicatedShortCode || 'None' }}</span>
                                </p>
                                <p>
                                    <span>Shared Code:</span> <span class="font-weight-bold">{{ sharedShortCode || 'None' }}</span>
                                </p>
                                <p>
                                    <span>Country:</span> <span class="font-weight-bold">{{ shortCodeCountry || 'Unknown' }}</span>
                                </p>
                            </div>

                            <!-- Show the info icon with the information from the version description -->
                            <Icon type="ios-information-circle-outline" :size="16" />
                        </Poptip>
                    </span>

                </div>

                <transition name="slide-right-fade">

                    <div v-show="isHovering" class="project-footer clearfix">

                        <div class="float-right">

                            <!-- Delete project -->
                            <Button type="dashed" size="small" class="text-danger" @click.native.stop="handleOpenDeleteProjectModal()">Delete</Button>

                            <!-- Clone project -->
                            <Button type="dashed" size="small" icon="ios-copy-outline" @click.native.stop="navigateToCloneProject()">Clone</Button>

                            <!-- View project -->
                            <Button type="dashed" size="small" class="text-primary" @click.native.stop="navigateToViewProject()">View</Button>

                        </div>

                    </div>

                </transition>

            </Col>

        </Row>

        <!--
            MODAL TO DELETE PROJECT
        -->
        <template v-if="isOpenDeleteProjectModal">

            <deleteProjectModal
                :index="index"
                :project="project"
                :projects="projects"
                @deleted="$emit('deleted')"
                @visibility="isOpenDeleteProjectModal = $event">
            </deleteProjectModal>

        </template>

    </Card>

</template>

<script>

    //  Get the custom mixin file
    var customMixin = require('./../../../../mixin.js').default;

    import deleteProjectModal from './deleteProjectModal.vue';

    export default {
        mixins: [customMixin],
        components: { deleteProjectModal },
        props: {
            index: {
                type: Number,
                default: null
            },
            project: {
                type: Object,
                default: null
            },
            projects: {
                type: Array,
                default:() => []
            }
        },
        data(){
            return {
                isHovering: false,
                isOpenDeleteProjectModal: false,
            }
        },
        computed: {
            projectUrl(){
                return this.project['_links']['self'].href;
            },
            shortCodeDetails(){
                return this.project['_embedded']['short_code'];
            },
            dedicatedShortCode(){
                return this.shortCodeDetails.dedicated_code;
            },
            sharedShortCode(){
                return this.shortCodeDetails.shared_code;
            },
            primaryShortCode(){
                return this.dedicatedShortCode || this.sharedShortCode;
            },
            shortCodeCountry(){
                return this.shortCodeDetails.country;
            },
            activeVersionDetails(){
                return this.project['_embedded']['active_version'];
            },
            activeVersionNumber(){
                return this.activeVersionDetails.number;
            },
            versionDescription(){
                return this.activeVersionDetails.description;
            },
            hexColor(){
                return this.project.hex_color;
            },
            avatarStyles(){
                return {
                    border: '1px solid #' + this.hexColor + ' !important',
                    background: '#' + this.hexColor + '20 !important',
                    color: '#'+this.hexColor + ' !important',
                }
            },
            statusText(){
                return this.project.online ? 'Online' : 'Offline'
            },
            status(){
                return this.project.online ? 'success' : 'error'
            }
        },
        methods: {
            navigateToCloneProject(){

                //  Navigate to clone project
                this.$router.push({ name: 'create-project', query: { project_url: encodeURIComponent(this.projectUrl) } });


            },
            navigateToViewProject(){

                if( this.projectUrl ){

                    //  Navigate to show the project
                    this.$router.push({ name: 'show-project-builder', params: { project_url: encodeURIComponent(this.projectUrl) } });

                }

            },
            handleOpenDeleteProjectModal(){
                this.isOpenDeleteProjectModal = true;
            }
        },
    }
</script>
