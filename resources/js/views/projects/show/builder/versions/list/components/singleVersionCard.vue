<template>

    <Card @mouseover.native="isHovering = true" 
          @mouseout.native="isHovering = false"
          @click.native="navigateToViewVersion()"
          class="sce-mini-card cursor-pointer mb-3" >
        
        <Row>

            <Col :span="24">

                <div class="d-flex pb-2">

                    <span :style="{ fontSize: '12px' }" class="cut-text">version</span>

                    <!-- Version Name: Note "firstLetter" filter is registered as a custom mixin -->
                    <span :style="versionNumberStyles" class="font-weight-bold mt-2 ml-2">{{ version.number }}</span>

                </div>

                <div class="sce-mini-card-body mb-3 py-2 pl-2 pr-5"
                    :style="{ textAlignLast: (isActive ? 'auto' : 'justify') }">

                    <span class="d-inline-block">
                        <!-- If we have the version info provided -->
                        <Poptip v-if="version.description" trigger="hover" :content="version.description"
                                 placement="top-start" word-wrap width="300">
                            <!-- Show the version info -->
                            <Icon type="ios-information-circle-outline" :size="16" />
                            <span>Info</span>
                        </Poptip>
                    </span>

                    <Tag v-if="isActive" color="green" class="ml-2">
                        Active Version
                    </Tag>

                </div>

                <transition name="slide-right-fade">

                    <div v-show="isHovering" class="version-footer clearfix">

                        <div class="float-right">
                            
                            <!-- Clone version -->
                            <Button type="dashed" size="small" icon="ios-copy-outline">Clone</Button>

                            <!-- View version -->
                            <Button type="dashed" size="small" class="text-primary" @click.native="navigateToViewVersion()">View</Button>

                        </div>

                    </div>

                </transition>

            </Col>

        </Row>
        
    </Card>

</template>

<script>

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../mixin.js').default;

    export default {
        mixins: [customMixin],
        props: {
            index: {
                type: Number,
                default: null
            },
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                isHovering: false
            }
        },
        computed: {
            isActive(){
                return (this.version.id == this.project.active_version_id);
            },
            versionUrl(){
                
                return this.version['_links']['self'].href;
            },
            projectUrl(){
                
                return this.project['_links']['self'].href;
            },
            versionNumberStyles(){
                return {
                    margin: '0 0 -5px 0',
                    lineHeight: '50px',
                    fontSize: '50px',
                }
            }
        },
        methods: {
            navigateToViewVersion(){
                /** Note that using router.push() or router.replace() does not allow us to make a
                 *  page refresh when visiting routes. This is undesirable at this moment since our 
                 *  parent component contains the <router-view />. When the page does not refresh, 
                 *  the <router-view /> is not able to receice the nested components defined in the 
                 *  route.js file. This means that we are then not able to render the nested 
                 *  components and present them. To counter this issue we must construct the 
                 *  href and use "window.location.href" to make a hard page refresh.
                 */
                var route = { name: 'show-project-version', params: { 
                        project_url: encodeURIComponent(this.projectUrl),
                        version_url: encodeURIComponent(this.versionUrl)
                    } };

                //  Contruct the full path url
                var href = window.location.origin + "/" + VueInstance.$router.resolve(route).href

                //  Visit the url
                window.location.href = href;
            }, 
        },
    }
</script>
