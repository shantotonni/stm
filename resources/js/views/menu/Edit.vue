<template>
    <div class="content">
        <div class="container-fluid">
            <breadcrumb :options="['Menu Edit']">
                <div class="col-sm-6">
                    <div class="float-right d-none d-md-block">
                        <router-link :to="{name:'MenuList'}" class="btn btn-primary float-right" type="button">
                            <i class="mdi mdi-keyboard-backspace mr-2"></i> Back
                        </router-link>
                    </div>
                </div>
            </breadcrumb>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <form class="repeater" @submit.prevent="updateMenu" @keydown="form.onKeydown($event)">
                                <div data-repeater-list="group-a">
                                    <div class="row">
                                        <div class="form-group col-lg-2">
                                            <label for="NavHeader">Nav Header</label>
                                            <input type="text" class="form-control" id="NavHeader" :class="{ 'is-invalid': form.errors.has('NavHeader') }" v-model="form.NavHeader" name="NavHeader" placeholder="Nav Header">
                                            <div class="error" v-if="form.errors.has('NavHeader')" v-html="form.errors.get('NavHeader')" />
                                        </div>
                                        <div class="form-group col-lg-2">
                                            <label for="NavItem">Nav Item</label>
                                            <input type="text" class="form-control" id="NavItem" :class="{ 'is-invalid': form.errors.has('NavItem') }" v-model="form.NavItem" name="NavItem" placeholder="Nav Item">
                                            <div class="error" v-if="form.errors.has('NavItem')" v-html="form.errors.get('NavItem')" />
                                        </div>
                                        <div class="form-group col-lg-2">
                                            <label for="NavItemIcon">Nav Item Icon</label>
                                            <input type="text" class="form-control" id="NavItemIcon" :class="{ 'is-invalid': form.errors.has('NavItemIcon') }" v-model="form.NavItemIcon" name="NavItemIcon" placeholder="Nav Item Icon">
                                            <div class="error" v-if="form.errors.has('NavItemIcon')" v-html="form.errors.get('NavItemIcon')" />
                                        </div>
                                        <div class="form-group col-lg-2">
                                            <label for="NavItemDetails">Nav Item Details</label>
                                            <input type="text" class="form-control" id="NavItemDetails" :class="{ 'is-invalid': form.errors.has('NavItemDetails') }" v-model="form.NavItemDetails" name="NavItemDetails" placeholder="Nav Item Details">
                                            <div class="error" v-if="form.errors.has('NavItemDetails')" v-html="form.errors.get('NavItemDetails')" />
                                        </div>
                                        <div class="form-group col-lg-2">
                                            <label for="NavItemDetailsIcon">Nav Item Details Icon</label>
                                            <input type="text" class="form-control" id="NavItemDetailsIcon" :class="{ 'is-invalid': form.errors.has('NavItemDetailsIcon') }" v-model="form.NavItemDetailsIcon" name="NavItemDetailsIcon" placeholder="Nav Item DetailsIcon">
                                            <div class="error" v-if="form.errors.has('NavItemDetailsIcon')" v-html="form.errors.get('NavItemDetailsIcon')" />
                                        </div>
                                        <div class="form-group col-lg-2">
                                            <label for="Link">Link</label>
                                            <input type="text" class="form-control" id="Link" :class="{ 'is-invalid': form.errors.has('Link') }" v-model="form.Link" name="Link" placeholder="Link">
                                            <div class="error" v-if="form.errors.has('Link')" v-html="form.errors.get('Link')" />
                                        </div>
                                        <div class="form-group col-lg-2">
                                            <label for="ReportOrder">Report Order</label>
                                            <input type="number" class="form-control" id="ReportOrder" :class="{ 'is-invalid': form.errors.has('ReportOrder') }" v-model="form.ReportOrder" name="ReportOrder" placeholder="Report Order">
                                            <div class="error" v-if="form.errors.has('ReportOrder')" v-html="form.errors.get('ReportOrder')" />
                                        </div>

                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success mo-mt-2 float-right" value="Add Menu">Update Menu</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
document.title = 'Menu Edit | Diesel Engine';
export default {
    name: "Edit",
    data() {
        return {
            form: new Form({
                NavHeader: '',
                NavItem: '',
                NavItemIcon: '',
                NavItemDetails: '',
                NavItemDetailsIcon: '',
                Link: '',
                ReportOrder: '',
            }),
            isLoading: false
        }
    },
    created() {
        axios.get(`/api/menu/${this.$route.params.MenuId}/edit`).then((response)=>{
            this.form.fill(response.data.data);
        });
    },
    methods: {
        updateMenu(){
            this.form.put(`/api/menu/${this.$route.params.MenuId}`).then((response)=>{
                console.log(response)
                this.$toaster.success(response.data.message);
                this.$router.push({name : 'MenuList'});
            }).catch((error)=>{
                this.$toaster.error('Something went wrong')
            })
        },
    }
}
</script>

<style scoped>

</style>
