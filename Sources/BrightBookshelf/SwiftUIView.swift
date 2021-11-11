//
//  SwiftUIView.swift
//  BrightBookshelf
//
//  Created by ZHANG on 11/11/2021.
//

import SwiftUI

struct SwiftUIView: View {
    var body: some View {
        ZStack(){
            Image("bookshelfBackground")
                .resizable(resizingMode: .tile)
                .opacity(0.5)
                .edgesIgnoringSafeArea(.all)
                .scaledToFill()
            VStack {
                Text("WELCOME")
                    .font(.largeTitle)
                    .fontWeight(.bold)
                    .foregroundColor(Color.white)
                    .padding()
                Text("TO")
                    .font(.largeTitle)
                    .foregroundColor(Color.white)
                    .padding()
                Text("BOOKMARK!")
                    .font(.largeTitle)
                    .fontWeight(.bold)
                    .foregroundColor(Color.white)
                    .padding()
            
            }
           
            
        }
    }
}


struct SwiftUIView_Previews: PreviewProvider {
    static var previews: some View {
        SwiftUIView()
    }
}
