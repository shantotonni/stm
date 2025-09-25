export const mutations = {
    submitButtonLoadingStatus(state, payload) {
        state.isSubmitButtonLoading = payload
    },
    supportingData(state, payload) {
    },
    me(state, payload) {
        state.me = payload
    },
    eStatement(state, payload) {
        state.eStatement = payload
    },
    getAllUserMenu(state, data){
        return state.allUserMenu = data;
    },
    filterData(state, data){
        return state.allFilterVal = data;
    },
    HostelFilterData(state, data){
        return state.allHostelFilterVal = data;
    },
}
