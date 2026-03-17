#include <stdio.h>

int main() {
	int a;
	float b;
	char c;
	scanf("%d %f %c",&a,&b,&c);
	if(b>=2.5){
		if(b>=3){
			printf("approved");
		}
		else if(a>=2){
			if(c=='Y'){
				printf("approved");
			}
			else{
				printf("not approved\n");
				printf("no help");
			}
		}
		else{
			printf("not approved\n");
			printf("year < 2");
		}
	}
	else{
		printf("not approved\n");
		printf("grade < 2.50");
	}
}
